<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A moment in an audio/video Discuss-it comment that a reply points to.  A reply has at most one.
 *
 * - discussion_comment_id: the reply
 * - referenced_discussion_comment_id: the audio/video comment (same thread) being pointed to
 * - seconds: where in that comment's media
 *
 * There are no foreign keys; rows are removed by DiscussionComment's "deleting" event, and reads skip any
 * row whose referenced comment no longer exists.
 */
class DiscussionCommentTimestamp extends Model
{
    protected $guarded = [];

    /**
     * Normalizes the timestamps sent by the client to [['referenced_discussion_comment_id' => int, 'seconds' => float], ...]
     *
     * @param mixed $timestamps
     * @return array
     */
    public static function normalize($timestamps): array
    {
        $normalized = [];
        if (!is_array($timestamps)) {
            return $normalized;
        }
        foreach ($timestamps as $timestamp) {
            $timestamp = (array)$timestamp;
            $normalized[] = [
                'referenced_discussion_comment_id' => (int)($timestamp['discussion_comment_id'] ?? 0),
                'seconds' => round(max(0, (float)($timestamp['seconds'] ?? 0)), 2)
            ];
        }
        return $normalized;
    }

    /**
     * Why these timestamps can't be attached to a reply in this thread, or null if they can.
     *
     * @param array $timestamps normalized
     * @param int $discussion_id the thread of the reply (0 for a new thread)
     * @param int $reply_discussion_comment_id the reply itself, when editing
     * @return string|null
     */
    public static function validationError(array $timestamps, int $discussion_id, int $reply_discussion_comment_id = 0): ?string
    {
        if (!$timestamps) {
            return null;
        }
        if (!$discussion_id) {
            return 'Timestamps can only be added to replies in an existing thread.';
        }
        if (count($timestamps) > 1) {
            return 'A reply can only have one timestamp.';
        }
        $referenced_ids = array_values(array_unique(array_column($timestamps, 'referenced_discussion_comment_id')));
        $referenced_comments = DB::table('discussion_comments')
            ->whereIn('id', $referenced_ids)
            ->select('id', 'discussion_id', 'file')
            ->get()
            ->keyBy('id');
        foreach ($referenced_ids as $referenced_id) {
            $referenced_comment = $referenced_comments[$referenced_id] ?? null;
            if (!$referenced_comment) {
                return 'One of the comments you added a timestamp for no longer exists.';
            }
            if ((int)$referenced_comment->discussion_id !== $discussion_id) {
                return 'Timestamps can only point to comments in the same thread.';
            }
            if (!$referenced_comment->file) {
                return 'Timestamps can only point to audio or video comments.';
            }
            if ($reply_discussion_comment_id && $referenced_id === $reply_discussion_comment_id) {
                return 'A comment cannot point to itself.';
            }
        }
        return null;
    }

    /**
     * @param int $discussion_comment_id
     * @param array $timestamps normalized
     * @return void
     */
    public static function storeForComment(int $discussion_comment_id, array $timestamps): void
    {
        $now = now();
        $rows = [];
        foreach (array_values($timestamps) as $order => $timestamp) {
            $rows[] = ['discussion_comment_id' => $discussion_comment_id,
                'referenced_discussion_comment_id' => $timestamp['referenced_discussion_comment_id'],
                'seconds' => $timestamp['seconds'],
                'order' => $order,
                'created_at' => $now,
                'updated_at' => $now];
        }
        if ($rows) {
            DB::table('discussion_comment_timestamps')->insert($rows);
        }
    }

    /**
     * When editing a reply, timestamps can be removed but not added.
     *
     * @param int $discussion_comment_id
     * @param array $keep_timestamp_ids
     * @return void
     */
    public static function keepOnly(int $discussion_comment_id, array $keep_timestamp_ids): void
    {
        DB::table('discussion_comment_timestamps')
            ->where('discussion_comment_id', $discussion_comment_id)
            ->whereNotIn('id', array_map('intval', $keep_timestamp_ids))
            ->delete();
    }

    /**
     * Removes timestamps belonging to this comment and timestamps in other replies that point to it.
     *
     * @param int $discussion_comment_id
     * @return void
     */
    public static function deleteForComment(int $discussion_comment_id): void
    {
        DB::table('discussion_comment_timestamps')
            ->where('discussion_comment_id', $discussion_comment_id)
            ->orWhere('referenced_discussion_comment_id', $discussion_comment_id)
            ->delete();
    }

    /**
     * Timestamps keyed by reply id.  Only rows whose referenced comment still exists are returned.
     *
     * @param array $discussion_comment_ids
     * @return array
     */
    public static function byCommentIds(array $discussion_comment_ids): array
    {
        $timestamps_by_comment_id = [];
        if (!$discussion_comment_ids) {
            return $timestamps_by_comment_id;
        }
        $rows = DB::table('discussion_comment_timestamps')
            ->join('discussion_comments AS referenced', 'discussion_comment_timestamps.referenced_discussion_comment_id', '=', 'referenced.id')
            ->join('users', 'referenced.user_id', '=', 'users.id')
            ->whereIn('discussion_comment_timestamps.discussion_comment_id', $discussion_comment_ids)
            ->select('discussion_comment_timestamps.id',
                'discussion_comment_timestamps.discussion_comment_id',
                'discussion_comment_timestamps.referenced_discussion_comment_id',
                'discussion_comment_timestamps.seconds',
                DB::raw('CONCAT(users.first_name, " ", users.last_name) AS referenced_created_by_name'))
            ->orderBy('discussion_comment_timestamps.order')
            ->orderBy('discussion_comment_timestamps.id')
            ->get();
        foreach ($rows as $row) {
            $timestamps_by_comment_id[$row->discussion_comment_id][] = [
                'id' => (int)$row->id,
                'referenced_discussion_comment_id' => (int)$row->referenced_discussion_comment_id,
                'seconds' => (float)$row->seconds,
                'referenced_created_by_name' => $row->referenced_created_by_name];
        }
        return $timestamps_by_comment_id;
    }
}
