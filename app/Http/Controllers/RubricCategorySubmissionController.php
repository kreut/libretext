<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Traits\DateFormatter;
use App\Question;
use App\ReportToggle;
use App\RubricCategory;
use App\RubricCategorySubmission;
use App\RubricCriteriaTest;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class RubricCategorySubmissionController extends Controller
{
    use DateFormatter;

    /**
     * @param Request $request
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @param RubricCategory $rubricCategory
     * @return array
     * @throws Exception
     */
    public function testRubricCriteria(Request                  $request,
                                       RubricCategorySubmission $rubricCategorySubmission,
                                       RubricCategory           $rubricCategory): array
    {

        try {
            $authorized = Gate::inspect('testRubricCriteria', $rubricCategorySubmission);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $rubricCategory = $rubricCategory->find($rubricCategorySubmission->rubric_category_id);
            $question = Question::find($rubricCategory->question_id);
            $grading_style = DB::table('grading_styles')->where('id', $question->grading_style_id)->first();
            $grading_style_description = $grading_style ? $grading_style->description : '';
            $rubricCriteriaTest = new RubricCriteriaTest();
            $rubricCriteriaTest->criteria = $request->rubric_criteria;
            $rubricCriteriaTest->status = 'pending';
            $rubricCriteriaTest->save();

            $rubricCategorySubmission->postToAI(
                $rubricCriteriaTest,
                $rubricCategorySubmission->user_id,
                0,
                'criteria-test-' . $rubricCriteriaTest->id,
                $rubricCategorySubmission->submission,
                $rubricCategory->score,
                $question->purpose,
                $request->rubric_criteria,
                $rubricCategory->category,
                $grading_style_description,
                '/api/open-ai/results/testing');
            $rubricCriteriaTest->refresh();
            $response['type'] = $rubricCriteriaTest->status;
            $response['message'] = $rubricCriteriaTest->message;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error testing the rubric. Please try again.";

        }
        return $response;
    }

    /**
     * @param Request $request
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @param RubricCategory $rubricCategory
     * @return array
     * @throws Exception
     */
    public function updateCustom(Request                  $request,
                                 RubricCategorySubmission $rubricCategorySubmission,
                                 RubricCategory           $rubricCategory): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateCustom', $rubricCategorySubmission);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $rubric_category = $rubricCategory->where('id', $rubricCategorySubmission->rubric_category_id)->first();
        $percent = str_replace(' % ', '', $rubric_category->percent);
        if ($request->score > $percent || $request->score < 0) {
            $response['message'] = "The score must be between 0 and $percent.";
            return $response;
        }
        try {
            $data = ['custom_score' => $request->custom_score, 'custom_feedback' => $request->custom_feedback];
            $rubricCategorySubmission->update($data);
            $response['type'] = 'success';
            $response['message'] = "The score and feedback have been updated for the $rubric_category->category section.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving the score and feedback. Please try again.";

        }
        return $response;

    }


    /**
     * @param Request $request
     * @param RubricCategory $rubricCategory
     * @param Assignment $assignment
     * @param Question $question
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @return array
     * @throws Exception
     */
    public function store(Request                  $request,
                          RubricCategory           $rubricCategory,
                          Assignment               $assignment,
                          Question                 $question,
                          RubricCategorySubmission $rubricCategorySubmission): array
    {
        $response['type'] = 'error';
        $assignment = Assignment::find($assignment->id);
        try {
            $authorized = Gate::inspect('store', [$rubricCategorySubmission, $assignment, $assignment->id, $question->id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            if (!$request->submission) {
                $response['message'] = 'This field is required . ';
                return $response;
            }
            $submission = $request->submission;
            $rubricCategorySubmission = RubricCategorySubmission::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'assignment_id' => $assignment->id,
                    'rubric_category_id' => $rubricCategory->id
                ],
                ['submission' => $submission]);
            $rubricCategorySubmission->initProcessing($rubricCategory, $rubricCategorySubmission, $assignment->id, $submission);
            $response['message'] = "The submission for '$rubricCategory->category' has been accepted.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving your $rubricCategory->category submission.  Please try again.";
        }
        return $response;
    }


    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param User $user
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @param ReportToggle $reportToggle
     * @return array
     * @throws Exception
     */
    public function getByAssignmentQuestionAndUser(Assignment               $assignment,
                                                   Question                 $question,
                                                   User                     $user,
                                                   RubricCategorySubmission $rubricCategorySubmission,
                                                   ReportToggle             $reportToggle): array
    {
        $response['type'] = 'error';

        try {
            $authorized = Gate::inspect('getByAssignmentQuestionAndUser', [$rubricCategorySubmission, $assignment, $user]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $select = 'rubric_category_submissions.*';

            if (request()->user()->role === 3) {
                $select = $assignment->show_scores
                    ? ['rubric_categories.score', 'rubric_category_submissions.*']
                    : ['rubric_category_submissions.id', 'rubric_category_submissions.rubric_category_id', 'rubric_category_submissions.submission','rubric_category_submissions.updated_at'];
            }

            $rubric_category_submissions = DB::table('rubric_category_submissions')
                ->join('rubric_categories', 'rubric_category_submissions.rubric_category_id', '=', 'rubric_categories.id')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', $user->id)
                ->select($select)
                ->get();

            if (request()->user()->role === 3) {
                $report_toggle = $reportToggle->where('assignment_id', $assignment->id)->where('question_id', $question->id)->first();
                if (!$report_toggle) {
                    $report_toggle = ['section_scores' => 0, 'comments' => 0, 'criteria' => 0];
                }
                $rubric_category_submissions = $reportToggle->getShownReportItems($rubric_category_submissions, $report_toggle);
            } else {
                $report_toggle = ['section_scores' => 1, 'comments' => 1, 'criteria' => 1];
            }
            foreach ($report_toggle as $key => $value) {
                $report_toggle[$key] = (bool)$value;
            }
            foreach ($rubric_category_submissions as $rubric_category_submission) {
                $rubric_category_submission->updated_at = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($rubric_category_submission->updated_at, request()->user()->time_zone);
            }
            $response['rubric_category_submissions'] = $rubric_category_submissions;
            $response['show_scores'] = in_array(request()->user()->role, [2, 4]) || $assignment->show_scores;
            $response['report_toggle'] = $report_toggle;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your rubric category submissions.  Please try again or contact us for help.";
        }
        return $response;


    }
}