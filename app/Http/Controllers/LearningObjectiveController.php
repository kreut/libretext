<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\LearningObjective;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreLearningObjective;

class LearningObjectiveController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(LearningObjective $learningObjective)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('viewAny', $learningObjective);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        $response['type'] = 'success';
        $response['learning_objectives'] = DB::table('learning_objectives')
            ->orderBy('learning_objective')
            ->get()
            ->pluck('learning_objective');
        return $response;

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
     *  Store a newly created resource in storage.
     *
     * @param StoreLearningObjective $request
     * @param LearningObjective $learningObjective
     * @return mixed
     * @throws \Exception
     */
    public function store(StoreLearningObjective $request, LearningObjective $learningObjective)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $learningObjective);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';
        try {
            $data = $request->validated();
            LearningObjective::create(
                ['learning_objective' => $data['learning_objective'],
                   'user_id' => Auth::user()->id
                ]
            );
            $response['type'] = 'success';
            $response['message'] = "The learning objective has been attached to the remediation.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the learning objective.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * Display the specified resource.
     *
     * @param  \App\LearningObjective  $learningObjective
     * @return \Illuminate\Http\Response
     */
    public function show(LearningObjective $learningObjective)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\LearningObjective  $learningObjective
     * @return \Illuminate\Http\Response
     */
    public function edit(LearningObjective $learningObjective)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\LearningObjective  $learningObjective
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LearningObjective $learningObjective)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\LearningObjective  $learningObjective
     * @return \Illuminate\Http\Response
     */
    public function destroy(LearningObjective $learningObjective)
    {
        //
    }
}
