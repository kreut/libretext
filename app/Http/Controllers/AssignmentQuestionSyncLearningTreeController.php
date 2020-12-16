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
            $question_id = $Question->getQuestionIdsByPageId($learningTree->root_node_page_id, false)[0];
            DB::beginTransaction();
            DB::table('assignment_question')
                ->insert([
                    'assignment_id' => $assignment->id,
                    'question_id' => $question_id,
                    'points' => $assignment->default_points_per_question //don't need to test since tested already when creating an assignment
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
