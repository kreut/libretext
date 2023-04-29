<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
use App\ReportToggle;
use App\RubricCategory;
use App\RubricCategorySubmission;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class RubricCategorySubmissionController extends Controller
{
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
        $rubric_category = $rubricCategory->where('id', $rubricCategorySubmission->rubric_category_id)->first();
        $percent = str_replace('%', '', $rubric_category->percent);
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
                $response['message'] = 'This field is required.';
                return $response;
            }
            $submission = $request->submission;
            $rubricCategorySubmission = RubricCategorySubmission::updateOrCreate(
                ['user_id' => $request->user()->id,
                    'rubric_category_id' => $rubricCategory->id],
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
            if ($user->role === 3) {
                $select = $assignment->show_scores
                    ? ['rubric_categories.percent', 'rubric_category_submissions.*']
                    : ['rubric_category_submissions.id', 'rubric_category_submissions.rubric_category_id', 'rubric_category_submissions.submission'];
            }
            $rubric_category_submissions = DB::table('rubric_category_submissions')
                ->join('rubric_categories', 'rubric_category_submissions.rubric_category_id', '=', 'rubric_categories.id')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', $user->id)
                ->select($select)
                ->get();
            if ($user->role === 3) {
                $report_toggle = $reportToggle->where('assignment_id', $assignment->id)->where('question_id', $question->id)->first();
                if (!$report_toggle) {
                    $report_toggle = ['points' => 0, 'comments' => 0, 'criteria' => 0];
                }

                $rubric_category_submissions = $reportToggle->getShownReportItems($rubric_category_submissions, $report_toggle);
            }

            $response['rubric_category_submissions'] = $rubric_category_submissions;
            $response['show_scores'] = $assignment->show_scores;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your rubric category submissions.  Please try again or contact us for help.";
        }
        return $response;


    }
}
