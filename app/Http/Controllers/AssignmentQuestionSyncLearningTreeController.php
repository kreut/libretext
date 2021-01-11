<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Question;
use App\LearningTree;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

use App\Exceptions\Handler;
use \Exception;

class AssignmentQuestionSyncLearningTreeController extends Controller
{
    public function store(Assignment $assignment, LearningTree $learningTree, AssignmentSyncQuestion $assignmentSyncQuestion, Question $Question)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('add', [$assignmentSyncQuestion, $assignment]);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $question_id = $Question->getQuestionIdsByPageId($learningTree->root_node_page_id, $learningTree->root_node_library, false)[0];
            $in_assignment = DB::table('assignment_question')->where('assignment_id', $assignment->id)
                ->where('question_id', $question_id)->get()->isNotEmpty();
            if ($in_assignment) {
                $response['message'] = 'That Learning Tree is already in the assignment.';
                return $response;

            }

            DB::beginTransaction();
            DB::table('assignment_question')
                ->insert([
                    'assignment_id' => $assignment->id,
                    'question_id' => $question_id,
                    'points' => $assignment->default_points_per_question, //don't need to test since tested already when creating an assignment
                    'open_ended_submission_type' => 0
            ]);
            $assignment_question_id = DB::getPdo()->lastInsertId();
            DB::table('assignment_question_learning_tree')
                ->insert([
                    'assignment_question_id' => $assignment_question_id,
                    'learning_tree_id' => $learningTree->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The Learning Tree has been added to the assignment.';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error adding the Learning Tree to the assignment.  Please try again or contact us for assistance.";
        }

        return $response;

    }

}
