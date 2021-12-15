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


    public function getDescription(Request      $request,
                                   LearningTree $learning_tree,
                                   string       $library,
                                   int          $page_id): array
    {

        $response['type'] = 'error';
        $question = DB::table('questions')->where('library', $library)
            ->where('page_id', $page_id)
            ->first();
        if (!$question) {
            $response['message'] = "A question with library '$library' and page id '$page_id' does not exist.";
            return $response;
        }
        try {
            //check to see if they have one
            $branch = DB::table('branches')
                ->where('user_id', $request->user()->id)
                ->where('learning_tree_id', $learning_tree->id)
                ->where('question_id', $question->id)
                ->first();
            if ($branch) {
                $response['description'] = $branch->description;
            } else {
                //try someone else's
                $branch = DB::table('branches')
                    ->where('learning_tree_id', $learning_tree->id)
                    ->where('question_id', $question->id)
                    ->first();
                $response['description'] = $branch ? $branch->description : '';
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the branch descriptions.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    public function getDescriptions(Request $request
    ): array
    {

        $response['type'] = 'error';
        try {
            $libraries_and_page_ids = $request->libraries_and_page_ids;
            $learning_tree_owner_id= DB::table('assignments')
                ->join('courses', 'assignments.course_id', '=','courses.user_id')
                ->select('user_id')
                ->first()
                ->user_id;
            if (!$libraries_and_page_ids) {
                $response['message'] = "There are no libraries and page ids.";
                return $response;
            }
            $branch_descriptions = [];
            foreach ($libraries_and_page_ids as $key => $library_and_page_id) {
                $question = DB::table('questions')
                    ->where('library', $library_and_page_id['library'])
                    ->where('page_id', $library_and_page_id['pageId'])
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

