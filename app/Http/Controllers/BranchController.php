<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\LearningTree;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{



    public function getDescriptions(Request $request
    ): array
    {

        $response['type'] = 'error';
        try {
            $question_ids = $request->question_ids;
            $learning_tree_owner_id= DB::table('assignments')
                ->join('courses', 'assignments.course_id', '=','courses.user_id')
                ->select('user_id')
                ->first()
                ->user_id;
            if (!$question_ids) {
                $response['message'] = "There are no question ids.";
                return $response;
            }
            $branch_descriptions = [];
            foreach ($question_ids as $key => $question_id) {
                $question = DB::table('questions')
                    ->where('id', $question_id)
                    ->first();
                $branch = DB::table('branches')
                    ->where('learning_tree_id', $request->learning_tree_id)
                    ->where('user_id', $learning_tree_owner_id)
                    ->where('question_id', $question->id)
                    ->first();
                if ($branch) {
                    $branch_descriptions[] = $branch->description;
                } else if ($question->title) {
                    $branch_descriptions[] = $question->title;
                } else {
                    $branch_descriptions[] = "$question->id does not have a branch description in this tree.";
                }
            }
            $response['branch_descriptions'] = $branch_descriptions;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the branch descriptions.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}

