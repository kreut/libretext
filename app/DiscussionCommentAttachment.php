<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * A file (PDF, image, Word, Excel, PowerPoint) attached to a Discuss-it comment.  A comment can have up to MAX_PER_COMMENT.
 *
 * Files are uploaded straight to S3 by the browser (pre-signed URL, "discuss-it-comments" upload type), so their type
 * and size are checked here, when the comment is saved.  There are no foreign keys: rows are removed by
 * DiscussionComment's "deleting" event.
 */
class DiscussionCommentAttachment extends Model
{
    protected $guarded = [];

    const MAX_PER_COMMENT = 5;

    const MAX_BYTES = 25 * 1024 * 1024;

    /**
     * The kinds of files students can attach, and their extensions.  SVG and HEIC are left out on purpose: SVG can
     * carry scripts and most browsers can't display HEIC.  Keep in sync with DiscussItFileUpload.vue.
     *
     * @return array
     */
    public static function fileTypes(): array
    {
        return ['pdf' => ['pdf'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'word' => ['doc', 'docx'],
            'excel' => ['xls', 'xlsx'],
            'powerpoint' => ['ppt', 'pptx']];
    }

    /**
     * pdf, image, word, excel, powerpoint, or null if the file isn't one of the allowed kinds.
     *
     * @param string|null $filename
     * @return string|null
     */
    public static function fileKind(?string $filename): ?string
    {
        $extension = strtolower(pathinfo((string)$filename, PATHINFO_EXTENSION));
        foreach (self::fileTypes() as $kind => $extensions) {
            if (in_array($extension, $extensions)) {
                return $kind;
            }
        }
        return null;
    }

    /**
     * Whether students may attach files to their comments.
     *
     * @param object|array|null $discuss_it_settings
     * @return bool
     */
    public static function attachmentsAllowed($discuss_it_settings): bool
    {
        $discuss_it_settings = (array)$discuss_it_settings;
        return +($discuss_it_settings['students_can_attach_files'] ?? 0) === 1;
    }

    /**
     * The name the file had on the student's computer, safe to show and to use as a download name.
     *
     * @param $original_filename
     * @return string
     */
    public static function cleanOriginalFilename($original_filename): string
    {
        $original_filename = basename(str_replace('\\', '/', strip_tags((string)$original_filename)));
        $original_filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $original_filename);
        return mb_substr(trim($original_filename), 0, 191);
    }

    /**
     * The new attachments sent by the client, as [['file' => ..., 'original_filename' => ...], ...]
     *
     * @param mixed $attachments
     * @return array
     */
    public static function normalize($attachments): array
    {
        $normalized = [];
        foreach (is_array($attachments) ? $attachments : [] as $attachment) {
            $attachment = (array)$attachment;
            $normalized[] = ['file' => basename((string)($attachment['file'] ?? '')),
                'original_filename' => self::cleanOriginalFilename($attachment['original_filename'] ?? '')];
        }
        return $normalized;
    }

    /**
     * Why these new attachments can't be saved, or null if they can.
     *
     * @param array $attachments normalized new attachments
     * @param int $number_already_attached attachments the comment keeps
     * @param object $discuss_it_settings
     * @return string|null
     */
    public static function validationError(array $attachments, int $number_already_attached, object $discuss_it_settings): ?string
    {
        if (!$attachments) {
            return null;
        }
        if (!self::attachmentsAllowed($discuss_it_settings)) {
            return 'Files cannot be attached to comments for this question.';
        }
        if (count($attachments) + $number_already_attached > self::MAX_PER_COMMENT) {
            return 'A comment can have at most ' . self::MAX_PER_COMMENT . ' files.';
        }
        $allowed_kinds = isset($discuss_it_settings->allowed_file_types)
            ? (array)$discuss_it_settings->allowed_file_types
            : array_keys(self::fileTypes());
        $dir = (new QuestionMediaUpload())->getDir();
        foreach ($attachments as $attachment) {
            $kind = self::fileKind($attachment['file']);
            if (!$kind || self::fileKind($attachment['original_filename']) !== $kind || !in_array($kind, $allowed_kinds)) {
                return 'That type of file is not allowed for this question.';
            }
            if (!Storage::disk('s3')->exists("$dir/{$attachment['file']}")) {
                return 'We could not find an uploaded file.  Please try uploading it again.';
            }
            if (Storage::disk('s3')->size("$dir/{$attachment['file']}") > self::MAX_BYTES) {
                return 'A file is too large.  Files can be at most 25 MB.';
            }
        }
        return null;
    }

    /**
     * @param int $discussion_comment_id
     * @param array $attachments normalized
     * @return void
     */
    public static function storeForComment(int $discussion_comment_id, array $attachments): void
    {
        $order = (int)DB::table('discussion_comment_attachments')
            ->where('discussion_comment_id', $discussion_comment_id)
            ->max('order');
        $now = now();
        $rows = [];
        foreach ($attachments as $attachment) {
            $rows[] = ['discussion_comment_id' => $discussion_comment_id,
                'file' => $attachment['file'],
                'original_filename' => $attachment['original_filename'],
                'order' => ++$order,
                'created_at' => $now,
                'updated_at' => $now];
        }
        if ($rows) {
            DB::table('discussion_comment_attachments')->insert($rows);
        }
    }

    /**
     * Removes the comment's attachments that aren't in $keep_attachment_ids.
     *
     * @param int $discussion_comment_id
     * @param array $keep_attachment_ids
     * @return void
     */
    public static function keepOnly(int $discussion_comment_id, array $keep_attachment_ids): void
    {
        DB::table('discussion_comment_attachments')
            ->where('discussion_comment_id', $discussion_comment_id)
            ->whereNotIn('id', array_map('intval', $keep_attachment_ids))
            ->delete();
    }

    /**
     * The rows only; the files stay in S3, as comment files do (a comment's deletion can still be rolled back, e.g. in
     * DiscussionCommentController::deletingWillMakeRequirementsNotSatisfied, and S3 deletions can't be).
     *
     * @param int $discussion_comment_id
     * @return void
     */
    public static function deleteForComment(int $discussion_comment_id): void
    {
        DB::table('discussion_comment_attachments')
            ->where('discussion_comment_id', $discussion_comment_id)
            ->delete();
    }

    /**
     * How many files the student attached to comments made in this assignment for this question.
     *
     * @param int $assignment_id
     * @param int $question_id
     * @param int $user_id
     * @return int
     */
    public static function numberAttachedByStudent(int $assignment_id, int $question_id, int $user_id): int
    {
        return DB::table('discussion_comment_attachments')
            ->join('discussion_comments', 'discussion_comment_attachments.discussion_comment_id', '=', 'discussion_comments.id')
            ->join('discussions', 'discussion_comments.discussion_id', '=', 'discussions.id')
            ->where('discussion_comments.posted_in_assignment_id', $assignment_id)
            ->where('discussions.question_id', $question_id)
            ->where('discussion_comments.user_id', $user_id)
            ->count();
    }

    /**
     * Attachments (with their links) keyed by comment id.
     *
     * @param array $discussion_comment_ids
     * @return array
     */
    public static function byCommentIds(array $discussion_comment_ids): array
    {
        $attachments_by_comment_id = [];
        if (!$discussion_comment_ids) {
            return $attachments_by_comment_id;
        }
        $rows = DB::table('discussion_comment_attachments')
            ->whereIn('discussion_comment_id', $discussion_comment_ids)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        foreach ($rows as $row) {
            $attachments_by_comment_id[$row->discussion_comment_id][] = array_merge([
                'id' => (int)$row->id,
                'original_filename' => $row->original_filename,
                'file_kind' => self::fileKind($row->file)],
                self::urls($row->file, $row->original_filename));
        }
        return $attachments_by_comment_id;
    }

    /**
     * - file_url: shown inline (images on the page, PDFs in the PDF viewer)
     * - download_url: saves the file under its original name (the only option for Office files, which browsers can't
     *   display)
     *
     * @param string $file
     * @param string|null $original_filename
     * @return array
     */
    public static function urls(string $file, ?string $original_filename): array
    {
        $content_types = ['pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $download_name = str_replace(['"', '\\', "\r", "\n"], '', $original_filename ?: $file);
        $inline_options = ['ResponseContentDisposition' => 'inline'];
        if (isset($content_types[$extension])) {
            $inline_options['ResponseContentType'] = $content_types[$extension];
        }
        //plain-ASCII name for older browsers, plus the full (possibly accented) name for the rest
        $download_options = ['ResponseContentDisposition' => 'attachment; filename="' . preg_replace('/[^\x20-\x7E]/', '_', $download_name) . '"; '
            . "filename*=UTF-8''" . rawurlencode($download_name)];
        $s3_key = (new QuestionMediaUpload())->getDir() . "/$file";
        try {
            return ['file_url' => Storage::disk('s3')->temporaryUrl($s3_key, now()->addHours(6), $inline_options),
                'download_url' => Storage::disk('s3')->temporaryUrl($s3_key, now()->addHours(6), $download_options)];
        } catch (\RuntimeException $e) {
            //only disks that can't make temporary links (e.g. a faked disk in tests) end up here
            return ['file_url' => '', 'download_url' => ''];
        }
    }
}
