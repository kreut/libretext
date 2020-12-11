<?php

namespace App\Http\Controllers;


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
    public function update(Request $request, LearningTree $learningTree)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $response['type'] = 'error';
        $learning_tree_old = json_decode($learningTree->learning_tree, true);
        $learning_tree_parsed = str_replace('\"', "'", $request->learning_tree);
        $learning_tree_new = json_decode($learning_tree_parsed, true);
        $no_change = $learning_tree_old === $learning_tree_new;

        if ($no_change) {
            $response['type'] = 'no_change';

        } else {
            try {
                $learningTree->learning_tree = $learning_tree_parsed;

                $learningTree->save();
                $response['type'] = 'success';
                $response['message'] = "The learning tree has been saved.";
                $response['no_change'] = $no_change;

            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);
                $response['message'] = "There was an error saving the learning tree.  Please try again or contact us for assistance.";
            }
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
            $learningTree->title = $data['title'];
            $learningTree->description = $data['description'];
            $learningTree->save();

            $response['type'] = 'success';
            $response['message'] = "The Learning Tree has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error upating the learning tree.  Please try again or contact us for assistance.";
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
            $validated_node = $this->validateLearningTreeNode($data['library'], $data['page_id']);
            if ($validated_node['type'] === 'error') {
                $response['message'] = $validated_node['message'];
                return $response;
            }
            if ($validated_node['body'] === '') {
                $response['message'] = "Are you sure that's a valid page id?  We're not finding any content on that page.";
                return $response;
            }
            $learningTree->title = $data['title'];
            $learningTree->description = $data['description'];
            $learningTree->user_id = Auth::user()->id;
            $learningTree->learning_tree = $this->getRootNode($validated_node['title'], $data['library'], $request->text, $request->color, $data['page_id']);
            $learningTree->save();

            $response['type'] = 'success';
            $response['learning_tree'] = $learningTree->learning_tree;
            $response['message'] = "The Learning Tree has been created.";
            $response['learning_tree_id'] = $learningTree->id;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function getRootNode(string $title, string $library_value, string $library_text, string $library_color, int $page_id)
    {
        $html = "<div class='blockelem noselect block' style='left: 363px; top: 215px; border: 2px solid; color: $library_color;'><input type='hidden' name='blockelemtype' class='blockelemtype' value='1'><input type='hidden' name='blockid' class='blockid' value='0'><div class='blockyleft'><p class='blockyname'><img src='/assets/img/{$library_value}.svg'><span data-library='$library_value'>{$library_text} - <span data-page_id='$page_id'>$page_id</span></p></div><div class='blockydiv'></div><div class='blockyinfo'>$title</div></div><div class='indicator invisible' style='left: 154px; top: 119px;'></div>";
        return <<<EOT
 {"html":"$html","blockarr":[{"childwidth":318,"parent":-1,"id":0,"x":825,"y":274,"width":318,"height":109}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"1"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block"},{"style":"left: 363px; top: 215px; border: 2px solid; color: {$library_color};"}]}]}
EOT;


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


    public function show(Request $request, LearningTree $learningTree)
    {
        //anybody who is logged in can do this!
        $response['type'] = 'error';
        try {

            $response['type'] = 'success';
            $response['learning_tree'] = $learningTree->learning_tree;
            $response['title'] = $learningTree->title;
            $response['description'] = $learningTree->description;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getDefaultLearningTree()
    {
        return <<<EOT
{"html":"<div class="blockelem noselect block" style="left: 363px; top: 215px; border: 2px solid; color: rgb(18, 123, 196);"><input type="hidden" name="blockelemtype" class="blockelemtype" value="1"><input type="hidden" name="blockid" class="blockid" value="0"><div class="blockyleft"><p class="blockyname"><img src="/assets/img/adapt.svg">Assessment</p></div><div class="blockydiv"></div><div class="blockyinfo">The original question.</div></div><div class="indicator invisible" style="left: 154px; top: 119px;"></div>","blockarr":[{"childwidth":318,"parent":-1,"id":0,"x":825,"y":274,"width":318,"height":109}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"1"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block"},{"style":"left: 363px; top: 215px; border: 2px solid; color: rgb(18, 123, 196);"}]}]}
EOT;

    }

    /**
     * Display the specified resource.
     *
     * @param \App\LearningTree $learningTree
     * @return \Illuminate\Http\Response
     */
    public function showByQuestion(Request $request, Question $question)
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
        $authorized = Gate::inspect('destroy', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $learningTree->delete();
            $response['type'] = 'info';
            $response['message'] = "The Learning Tree has been deleted.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the learning Tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function validateLearningTreeNode(string $library, int $pageId)
    {

        $response['type'] = 'error';
        try {
            $Query = new Query(['library' => $library]);
            $contents = $Query->getContentsByPageId($pageId);
            $response['body'] = $contents['body'];
            $response['title'] =$contents['@title'] ?? 'Title';
            $response['type'] = 'success';
        } catch (Exception $e) {
            if (strpos($e->getMessage(), '403 Forbidden') === false) {
                //some other error besides forbidden
                $h = new Handler(app());
                $h->report($e);
                $response['message'] = "We were not able to validate this Learning Tree node: {$e->getMessage()}.  Please double check your library and page id or contact us for assistance.";
            } else {
                try {
                    $contents = $Query->getBodyFromPrivatePage($pageId);
                    $response['body'] = $contents['@title'] ?? 'Title';
                    $response['title'] = $contents;
                    $response['type'] = 'success';
                } catch (Exception $e) {
                    $h = new Handler(app());
                    $h->report($e);
                    $response['message'] = "We were not able to validate this Learning Tree node: {$e->getMessage()}.  Please double check your library and page id or contact us for assistance.";
                }
            }
        }
        return $response;
    }
}
