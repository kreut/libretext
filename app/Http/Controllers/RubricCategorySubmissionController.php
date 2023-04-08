<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Question;
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
                          Question                 $question,
                          RubricCategorySubmission $rubricCategorySubmission): array
    {
        $response['type'] = 'error';
        $assignment_id = $rubricCategory->assignment_id;
        $assignment = Assignment::find($assignment_id);
        try {
            $authorized = Gate::inspect('store', [$rubricCategorySubmission, $assignment, $assignment_id, $question->id]);
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
            $rubricCategorySubmission->initProcessing($rubricCategory, $rubricCategorySubmission, $submission);
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
     * @param User $user
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @return array
     * @throws Exception
     */
    public function getByAssignmentAndUser(Assignment               $assignment,
                                           User                     $user,
                                           RubricCategorySubmission $rubricCategorySubmission): array
    {
        $response['type'] = 'error';

        try {
            $authorized = Gate::inspect('get', [$rubricCategorySubmission, $assignment, $user]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $rubric_category_submissions = DB::table('rubric_category_submissions')
                ->join('rubric_categories', 'rubric_category_submissions.rubric_category_id', '=', 'rubric_categories.id')
                ->where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->select('rubric_category_submissions.*')
                ->get();
            $response['rubric_category_submissions'] = $rubric_category_submissions;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your rubric category submissions.  Please try again or contact us for help.";
        }
        return $response;


    }
}
