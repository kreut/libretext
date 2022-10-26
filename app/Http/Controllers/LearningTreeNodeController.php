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
                                int          $question_id): array
    {

        $response['type'] = 'error';
        $question = DB::table('questions')->where('id', $question_id)->first();
        if (!$question) {
            $response['message'] = "A question with question ID '$question_id' does not exist.";
            return $response;
        }
        try {
            $last_learning_outcome = DB::table('learning_tree_node_learning_outcome')
                ->join('learning_outcomes',
                    'learning_tree_node_learning_outcome.learning_outcome_id', '=', 'learning_outcomes.id')
                ->where('user_id', $request->user()->id)
                ->select('subject')
                ->orderBy('learning_tree_node_learning_outcome.id', 'desc')
                ->first();


            $learning_outcome = DB::table('learning_tree_node_learning_outcome')
                ->join('learning_outcomes',
                    'learning_tree_node_learning_outcome.learning_outcome_id', '=', 'learning_outcomes.id')
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
            $learning_tree_node_description = DB::table('learning_tree_node_descriptions')
                ->where('learning_tree_id', $learning_tree->id)
                ->where('question_id', $question->id)
                ->where('user_id', $request->user()->id)
                ->first();

            $response['subject'] = $learning_outcome ? $learning_outcome->subject : ($last_learning_outcome ? $last_learning_outcome->subject : null);
            $response['learning_outcome'] = $learning_outcome ? ['id' => $learning_outcome->id, 'label' => $learning_outcome->description] : '';
            $response['title'] = $learning_tree_node_description && $learning_tree_node_description->title ? $learning_tree_node_description->title : $question->title;
            $response['notes'] = $learning_tree_node_description && $learning_tree_node_description->notes ? $learning_tree_node_description->notes : '';
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the node meta info.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
