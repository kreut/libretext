<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Question;
use App\RubricPointsBreakdown;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RubricPointsBreakdownController extends Controller
{
    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param RubricPointsBreakdown $rubricPointsBreakdown
     * @return array
     * @throws Exception
     */
    public function existsByAssignmentQuestion(Assignment            $assignment,
                                               Question              $question,
                                               RubricPointsBreakdown $rubricPointsBreakdown): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('existsByAssignmentQuestion', [$rubricPointsBreakdown, $assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $rubric_points_breakdown_exists = $rubricPointsBreakdown->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->exists();
            $response['rubric_points_breakdown_exists'] = $rubric_points_breakdown_exists;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error checking whether the rubric points breakdown exists.  Please try again or contact us for assistance.";

        }
        return $response;

    }


    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param User $user
     * @param RubricPointsBreakdown $rubricPointsBreakdown
     * @return array
     * @throws Exception
     */
    public function getByAssignmentQuestionUser(Assignment            $assignment,
                                                Question              $question,
                                                User                  $user,
                                                RubricPointsBreakdown $rubricPointsBreakdown)
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('getByAssignmentQuestionUser', [$rubricPointsBreakdown, $assignment, $user]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $assignment_question = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            $rubric = $assignment_question->custom_rubric ?: $question->rubric;
            if (!$rubric) {
                $rubric_points_breakdown = [];
                $rubric_points_breakdown_exists = false;

            } else {
                $rubric_points_breakdown = $rubricPointsBreakdown->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->where('user_id', $user->id)
                    ->first();
                $rubric_points_breakdown_exists = true;
                if (!$rubric_points_breakdown) {
                    $rubric_points_breakdown_exists = false;
                    $rubric_points_breakdown = json_decode($rubric, 1);
                    foreach ($rubric_points_breakdown['rubric_items'] as &$value) {
                        $value['points'] = '';
                        $value['percentage'] = '';
                    }
                    $rubric_points_breakdown = json_encode($rubric_points_breakdown);
                } else {
                    $rubric_points_breakdown = $rubric_points_breakdown->points_breakdown;
                }
            }
            $response['rubric_points_breakdown'] = $rubric_points_breakdown;
            $response['rubric_points_breakdown_exists'] = $rubric_points_breakdown_exists;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the rubric points breakdown.  Please try again or contact us for assistance.";

        }
        return $response;

    }
}
