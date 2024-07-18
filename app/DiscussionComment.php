<?php

namespace App;

use App\Traits;
use App\Helpers\Helper;
use DOMDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DiscussionComment extends Model
{
    use Traits\DateFormatter;

    /**
     * @param string $type
     * @param object $discuss_it_settings
     * @param $request
     * @return bool
     */
    public function satisfiedRequirement(string $type, object $discuss_it_settings, $request): bool
    {
        $satisfied_requirement = true;

        switch ($type) {
            case('text'):
                if ($discuss_it_settings->min_number_of_words) {
                    $satisfied_requirement = $this->countWords($request->text) >= $discuss_it_settings->min_number_of_words;
                }
                break;
            case('file'):
                $satisfied_requirement = $request->file_satisfied_requirement;
                break;

        }
        return $satisfied_requirement;
    }


    public function updateScore($discuss_it_settings,
                                array $discussion_comment_submission_results,
                                AssignmentSyncQuestion $assignmentSyncQuestion,
                                int $user_id,
                                Assignment $assignment,
                                Question $question,
                                Submission $submission,
                                SubmissionFile $submissionFile,
                                Score $score
    )
    {
        if (+$discuss_it_settings->auto_grade === 1
            && $discussion_comment_submission_results['satisfied_min_number_of_comments_requirement']
            && $discussion_comment_submission_results['satisfied_min_number_of_discussion_threads_requirement']) {

            $assignment_question = $assignmentSyncQuestion->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();

            $submission_file = $submissionFile->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', $user_id)
                ->first();
            if (!$submission_file) {
                $submissionFile = new  SubmissionFile();
                $submissionFile->assignment_id = $assignment->id;
                $submissionFile->question_id = $question->id;
                $submissionFile->user_id = $user_id;
                $submissionFile->type = 'discuss_it';
                $submissionFile->original_filename = '';
                $submissionFile->date_submitted = now();
                $submissionFile->submission = '';
                $submissionFile->date_graded = now();
                $submissionFile->grader_id = null;
                $submissionFile->score = $submission->applyLatePenalyToScore($assignment, $assignment_question->points);
                $submissionFile->save();
            }
            $score->updateAssignmentScore($user_id, $assignment->id, $assignment->lms_grade_passback === 'automatic');
        }
    }

    /**
     * @param Assignment $assignment
     * @param int $question_id
     * @param int $user_id
     * @param Discussion $discussion
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     */
    public
    function satisfiedRequirements(Assignment             $assignment,
                                   int                    $question_id,
                                   int                    $user_id,
                                   Discussion             $discussion,
                                   AssignmentSyncQuestion $assignmentSyncQuestion): array
    {
        $user = User::find($user_id);
        $assignment_id = $assignment->id;
        $discuss_it_settings = json_decode($assignmentSyncQuestion->discussItSettings($assignment_id, $question_id));
        $number_of_comments_that_satisfied_the_requirements = $this->numberOfCommentsThatSatisfiedTheRequirements($assignment_id, $question_id, $user_id);
        $number_of_discussion_threads_that_satisfied_the_requirements = $discussion->numberOfDiscussionsThatSatisfiedTheRequirements($assignment_id, $question_id, $user_id);

        $satisfied_min_number_of_comments_requirement = !$discuss_it_settings->min_number_of_comments
            && $number_of_comments_that_satisfied_the_requirements >= 1 || $discuss_it_settings->min_number_of_comments && $number_of_comments_that_satisfied_the_requirements >= $discuss_it_settings->min_number_of_comments;

        $satisfied_min_number_of_discussion_threads_requirement = !$discuss_it_settings->min_number_of_discussion_threads
            && $number_of_discussion_threads_that_satisfied_the_requirements >= 1 || $discuss_it_settings->min_number_of_discussion_threads && $number_of_discussion_threads_that_satisfied_the_requirements >= $discuss_it_settings->min_number_of_discussion_threads;


        $response['satisfied_min_number_of_comments_requirement'] = $satisfied_min_number_of_comments_requirement;
        $response['satisfied_min_number_of_discussion_threads_requirement'] = $satisfied_min_number_of_discussion_threads_requirement;
        $number_of_comments_plural = $number_of_comments_that_satisfied_the_requirements !== 1 ? 's' : '';
        $number_of_discussion_threads_plural = $number_of_discussion_threads_that_satisfied_the_requirements !== 1 ? 's' : '';
        $response['number_of_comments_submitted'] = $number_of_comments_that_satisfied_the_requirements;
        $response['number_of_discussion_threads_participated_in'] = $number_of_discussion_threads_that_satisfied_the_requirements;
        $response['number_of_comments_submitted_message'] = "You have submitted $number_of_comments_that_satisfied_the_requirements comment$number_of_comments_plural.";
        $response['number_of_discussion_threads_participated_in_message'] = "You have participated in $number_of_discussion_threads_that_satisfied_the_requirements discussion thread$number_of_discussion_threads_plural.";
        $response['min_number_of_comments_required'] = $discuss_it_settings->min_number_of_comments;
        $response['min_number_of_discussion_threads'] = $discuss_it_settings->min_number_of_discussion_threads;
        $submission_summary_info = DB::table('submission_files')
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->where('user_id', $user_id)
            ->first();

        $show_scores = $discuss_it_settings && +$discuss_it_settings->auto_grade || $assignment->show_scores || $assignment->assessment_type === 'real time';
        $response['show_scores'] = $show_scores;
        if (!$submission_summary_info) {
            $submission_summary = [
                'date_submitted' => 'N/A',
                'date_graded' => 'N/A',
                'score' => 'N/A',
                'grader_id' => null];
        } else {
            $submission_summary['date_submitted'] = $submission_summary_info->date_submitted ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($submission_summary_info->date_submitted, $user->time_zone, 'F d, Y g:i:s a') : 'N/A';
            $submission_summary['date_graded'] = $show_scores ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($submission_summary_info->date_graded, $user->time_zone, 'F d, Y g:i:s a') : 'N/A';
            $submission_summary['score'] = $show_scores ? Helper::removeZerosAfterDecimal($submission_summary_info->score) : 'N/A';
            $submission_summary['grader_id'] = $submission_summary_info->grader_id;
        }


        $response['submission_summary'] = $submission_summary;

        return $response;
    }

    /**
     * @param int $assignment_id
     * @param int $question_id
     * @param int $user_id
     * @return mixed
     */
    public
    function numberOfCommentsThatSatisfiedTheRequirements(int $assignment_id, int $question_id, int $user_id)
    {
        return $this->join('discussions', 'discussion_comments.discussion_id', '=', 'discussions.id')
            ->where('discussions.assignment_id', $assignment_id)
            ->where('discussions.question_id', $question_id)
            ->where('discussion_comments.user_id', $user_id)
            ->where('discussion_comments.satisfied_requirement', 1)
            ->count();
    }

    public
    function getDir(): string
    {
        return ('uploads/discuss-it/discussion-comments');
    }

    /**
     * @param $htmlString
     * @return int
     */
    function countWords($htmlString): int
    {
        // Load the HTML string into a DOMDocument
        $dom = new DOMDocument();
        @$dom->loadHTML($htmlString, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Count MathJax formulas and images as one word each
        $mathjaxCount = $dom->getElementsByTagName('span')->length;
        $imageCount = $dom->getElementsByTagName('img')->length;

        // Remove MathJax formulas and images from the content
        while ($dom->getElementsByTagName('span')->length > 0) {
            $node = $dom->getElementsByTagName('span')->item(0);
            $node->parentNode->removeChild($node);
        }
        while ($dom->getElementsByTagName('img')->length > 0) {
            $node = $dom->getElementsByTagName('img')->item(0);
            $node->parentNode->removeChild($node);
        }

        // Get the remaining text content
        $textContent = $dom->textContent;

        // Split the text into words
        $words = preg_split('/\s+/', trim($textContent), -1, PREG_SPLIT_NO_EMPTY);

        // Total word count
        return count($words) + $mathjaxCount + $imageCount;
    }
}
