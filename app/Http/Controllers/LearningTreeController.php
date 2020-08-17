<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\StoreLearningTree;
use App\LearningTree;
use App\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LearningTreeController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLearningTree $request, LearningTree $learningTree)
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';
        $learning_tree_parsed = str_replace('\"', "'", $request->learning_tree);

        try {

            $data = $request->validated();

            DB::table('learning_trees')
                ->updateOrInsert(
                    ['question_id' => $data['question_id'], 'user_id' => Auth::user()->id],
                    ['learning_tree' => $learning_tree_parsed]
                );

            $response['type'] = 'success';
            $response['message'] = "The learning tree has been saved.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function getLearningTreeByUserAndQuestionId($user_id, $question_id)
    {
        return DB::table('learning_trees')
            ->where('question_id', $question_id)
            ->where('user_id', $user_id)
            ->pluck('learning_tree');
    }

    public function getDefaultLearningTreeByQuestionId(int $question_id)
    {
        return DB::table('learning_trees')
            ->where('question_id', $question_id)
            ->orderBy('created_at', 'asc')
            ->pluck('learning_tree');
    }

    public function getDefaultLearningTree()
    {
       return  <<<EOT
{"html":"<div class='blockelem noselect block' style='left: 275px; top: 171px; border: 2px solid; color: rgb(18, 123, 196);'><input type='hidden' name='blockelemtype' class='blockelemtype' value='1'><input type='hidden' name='blockid' class='blockid' value='0'><div class='blockyleft'><p class='blockyname'><img src='/assets/img/adapt.svg'>Assessment</p></div><div class='blockydiv'></div><div class='blockyinfo'>This node represents the original question.</div></div><div class='indicator invisible' style='left: 154px; top: 119px;'></div>","blockarr":[{"childwidth":318,"parent":-1,"id":0,"x":795,"y":301,"width":318,"height":109}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"1"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block"},{"style":"left: 275px; top: 171px; border: 2px solid; color: rgb(18, 123, 196);"}]}]}
EOT;

               }

    /**
     * Display the specified resource.
     *
     * @param \App\LearningTree $learningTree
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Question $question)
    {
        //anybody who is logged in can do this!
        $response['type'] = 'error';
        try {
            $learning_tree = $this->getLearningTreeByUserAndQuestionId(Auth::user()->id, $question->id);

            if ($learning_tree->isEmpty()) {
                $learning_tree = $this->getDefaultLearningTreeByQuestionId($question->id);
            }

            if ($learning_tree->isEmpty()) {
                $learning_tree = $this->getDefaultLearningTree();
            }

            $response['type'] = 'success';
            $response['learning_tree'] = $learning_tree;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\LearningTree $learningTree
     * @return \Illuminate\Http\Response
     */
    public function edit(LearningTree $learningTree)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\LearningTree $learningTree
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LearningTree $learningTree)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\LearningTree $learningTree
     * @return \Illuminate\Http\Response
     */
    public function destroy(LearningTree $learningTree)
    {
        //
    }
}
