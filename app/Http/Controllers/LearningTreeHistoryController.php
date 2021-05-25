<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\LearningTree;
use App\LearningTreeHistory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LearningTreeHistoryController extends Controller
{
    public function updateLearningTreeFromHistory(LearningTree $learningTree,
                                                  LearningTreeHistory $learningTreeHistory)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('updateLearningTreeFromHistory', [$learningTreeHistory,$learningTree]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $current_learning_tree = $learningTreeHistory->where('learning_tree_id', $learningTree->id)
                ->orderBy('id', 'desc')
                ->first();
            DB::beginTransaction();
            $current_learning_tree->delete();

            $learning_tree_to_restore = $learningTreeHistory->where('learning_tree_id', $learningTree->id)
                ->orderBy('id', 'desc')
                ->first();
            if (!$learning_tree_to_restore) {
                $response['type'] = 'info';
                $response['message'] = 'There is nothing left to undo.';
                return $response;
            }

            $learningTree->learning_tree = $learning_tree_to_restore->learning_tree;

            $learningTree->save();
            $response['type'] = 'success';
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the learning tree from your history.  Please try again or contact us for assistance.";
        }

        return $response;

    }
}
