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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLearningTree $request, LearningTree $learningTree)
    {

        ///validate that not a student
        ///check that it doesn't exist
        /// check that the question is an integer
        ///


        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';
$learning_tree_parsed = str_replace('\"',"'", $request->learning_tree);

        try {

            $data = $request->validated();

            LearningTree::create(
                ['question_id' => $data['question_id'],
                    'user_id' => Auth::user()->id,
                    'learning_tree' => $learning_tree_parsed
                ]
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
            $learning_tree = DB::table('learning_trees')
                        ->where('question_id', $question->id)
                        ->where('user_id',Auth::user()->id)
                        ->pluck('learning_tree');
            if (!$learning_tree){




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
