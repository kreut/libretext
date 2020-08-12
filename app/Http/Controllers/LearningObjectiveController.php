<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\LearningObjective;
use App\LearningObjectiveNode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreLearningObjective;

class LearningObjectiveController extends Controller
{



    /**
     * Display the specified resource.
     *
     * @param \App\LearningObjective $learningObjective
     * @return \Illuminate\Http\Response
     */
    public function show(LearningObjective $learningObjective)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\LearningObjective $learningObjective
     * @return \Illuminate\Http\Response
     */
    public function edit(LearningObjective $learningObjective)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\LearningObjective $learningObjective
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LearningObjective $learningObjective)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\LearningObjective $learningObjective
     * @return \Illuminate\Http\Response
     */
    public function destroy(LearningObjective $learningObjective)
    {
        //
    }
}
