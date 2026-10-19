<?php

namespace App;

use App\Jobs\ConvertSubmissionPdfToImages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SubmissionFilePage extends Model
{
    protected $guarded = [];

    /**
     * Where the image for a page of an uploaded PDF lives, e.g.
     * assignments/12/pages/3f9c...a1/page-4.jpg for assignments/12/3f9c...a1.pdf
     *
     * @param int $assignment_id
     * @param string $submission
     * @param int $page
     * @return string
     */
    public static function s3Key(int $assignment_id, string $submission, int $page): string
    {
        $folder = pathinfo($submission, PATHINFO_FILENAME);
        return "assignments/$assignment_id/pages/$folder/page-$page.jpg";
    }

    /**
     * Which pages of the uploaded PDF belong to a question.
     *
     * - A PDF or image uploaded for one question (no page set): all of it (an image is one page).
     * - A page range from a compiled PDF: that range, clamped to the PDF.
     * - A start page with no end page (assigned before end pages existed): null, so the grader keeps the PDF view.
     *
     * @param int|null $page
     * @param int|null $end_page
     * @param int|null $number_of_pages
     * @return array|null [first, last]
     */
    public static function pageRange(?int $page, ?int $end_page, ?int $number_of_pages): ?array
    {
        if (!$number_of_pages) {
            return null;
        }
        if (!$page) {
            return [1, $number_of_pages];
        }
        if (!$end_page) {
            return null;
        }
        $first = min($page, $number_of_pages);
        return [$first, max($first, min($end_page, $number_of_pages))];
    }

    /**
     * The page images a grader should see for one student's submission to one question.
     *
     * @param int $assignment_id
     * @param string|null $submission
     * @param int|null $page
     * @param int|null $end_page
     * @return array ['status' => null|pending|processing|completed|failed, 'pages' => [...]]
     */
    public static function forSubmission(int $assignment_id, ?string $submission, ?int $page, ?int $end_page): array
    {
        $result = ['status' => null, 'pages' => []];
        if (!$submission || !ConvertSubmissionPdfToImages::canConvert($submission)) {
            return $result;
        }
        $conversion = SubmissionFileConversion::where('assignment_id', $assignment_id)
            ->where('submission', $submission)
            ->first();
        if (!$conversion) {
            return $result;
        }
        $result['status'] = $conversion->status;
        if ($conversion->status !== SubmissionFileConversion::COMPLETED) {
            return $result;
        }
        $range = self::pageRange($page, $end_page, $conversion->number_of_pages);
        if (!$range) {
            return $result;
        }
        $result['pages'] = self::where('assignment_id', $assignment_id)
            ->where('submission', $submission)
            ->whereBetween('page', $range)
            ->orderBy('page')
            ->get()
            ->map(function ($page_image) {
                return [
                    'page' => $page_image->page,
                    'width' => $page_image->width,
                    'height' => $page_image->height,
                    'url' => Storage::disk('s3')->temporaryUrl($page_image->s3_key, now()->addMinutes(120))
                ];
            })
            ->values()
            ->all();
        return $result;
    }
}
