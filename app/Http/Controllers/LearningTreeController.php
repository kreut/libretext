<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLearningTree;
use App\Http\Requests\StoreLearningTreeInfo;
use App\LearningTree;
use App\Query;
use App\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Exceptions\Handler;
use \Exception;

class LearningTreeController extends Controller
{


    public function index(Request $request, LearningTree $learningTree)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $response['type'] = 'error';

        try {
            $response['learning_trees'] = $learningTree->where('user_id', Auth::user()->id)->get();
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your learning trees.  Please try again or contact us for assistance.";
        }
        return $response;


    }

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

            LearningTree::updateOrCreate(
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

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateLearningTreeInfo(StoreLearningTreeInfo $request, LearningTree $learningTree)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';


        try {

            $data = $request->validated();
            $learningTree->title  = $data['title'];
            $learningTree->description = $data['description'];
            $learningTree->save();

            $response['type'] = 'success';
            $response['message'] = "The Learning Tree has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error udpating the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function storeLearningTreeInfo(StoreLearningTreeInfo $request, LearningTree $learningTree)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';


        try {

            $data = $request->validated();
            $learningTree->title  = $data['title'];
            $learningTree->description = $data['description'];
            $learningTree->user_id = Auth::user()->id;
            $learningTree->learning_tree = '';
            $learningTree->save();

            $response['type'] = 'success';
            $response['message'] = "The Learning Tree has been added.";
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
        return <<<EOT
{"html":"<div class='blockelem noselect block' style='left: 363px; top: 215px; border: 2px solid; color: rgb(18, 123, 196);'><input type='hidden' name='blockelemtype' class='blockelemtype' value='1'><input type='hidden' name='blockid' class='blockid' value='0'><div class='blockyleft'><p class='blockyname'><img src='/assets/img/adapt.svg'>Assessment</p></div><div class='blockydiv'></div><div class='blockyinfo'>The original question.</div></div><div class='indicator invisible' style='left: 154px; top: 119px;'></div>","blockarr":[{"childwidth":318,"parent":-1,"id":0,"x":825,"y":274,"width":318,"height":109}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"1"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block"},{"style":"left: 363px; top: 215px; border: 2px solid; color: rgb(18, 123, 196);"}]}]}
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
     * Display the specified resource.
     *
     * @param \App\LearningTree $learningTree
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, LearningTree $learningTree)
    {
        //anybody who is logged in can do this!
        $response['type'] = 'error';
        try {
            $learningTree->delete();
            $response['type'] = 'success';
            $response['message'] = "The Learning Tree has been deleted.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the learning Tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function validateRemediation(string $library, int $pageId)
    {

        $Query = new Query(['library' => $library]);
        $response['type'] = 'error';
        try {
            $Query->getContentsByPageId($pageId);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to validate this remediation.  Please double check your library and page id or contact us for assistance.";
        }
        return $response;

    }
}
