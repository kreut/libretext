<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\LearningTree;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningTreeNodeController extends Controller
{
    public function getMetaInfo(Request      $request,
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
            $skill = DB::table('learning_tree_node_skill')
                ->join('skills','learning_tree_node_skill.skill_id','=','skills.id')
                ->where('user_id', $request->user()->id)
                ->where('learning_tree_id', $learning_tree->id)
                ->where('question_id', $question->id)
                ->first();

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
            $response['skill'] = $skill ? $skill->title : '';
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the node meta info.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
