<?php

namespace App\Http\Controllers;

use App\Score;
use App\Course;
use App\Enrollment;
use Illuminate\Http\Request;

class ScoreController extends Controller
{

    public function index(Course $course)
    {
        //get all user_ids for the user enrolled in the course
        foreach ($course->enrolledUsers as $key => $user) {
            $enrolled_users[$user->id] = "$user->first_name $user->last_name";
        }

        //get all assignments in the course
        $assignments = $course->assignments->sortBy('due');

        if ($assignments->isEmpty()) {
            return ['hasAssignment' => false];
        }

        $scores = $course->scores;

        //organize the scores by user_id and assignment
        $scores_by_user_and_assignment = [];
        foreach ($scores as $score) {
            $scores_by_user_and_assignment[$score->user_id][$score->assignment_id] = $score->score;
        }

        //now fill in the actual scores
        $rows = [];
        foreach ($enrolled_users as $user_id => $name) {
            $columns = [];

            foreach ($assignments as $assignment) {
                $score = $scores_by_user_and_assignment[$user_id][$assignment->id] ?? '-';
                $columns[$assignment->id] = $score;
            }
            $columns['name'] = $name;
            $columns['userId'] = $user_id;
            $rows[] = $columns;
        }

        $fields = [['key' => 'name',
            'label' => 'Name',
            'sortable' => true,
            'stickyColumn' => true]];
        foreach ($assignments as $assignment) {
            $field = ['key' => "$assignment->id", 'label' => $assignment->name];
            array_push($fields, $field);
        }
        return compact('rows', 'fields') + ['hasAssignments' => true];

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
    public function store(Request $request)
    {
        //
    }

    /**
     *
     * Show the scores for a given course
     *
     * @param Course $course
     * @return array
     */

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Score $score
     * @return \Illuminate\Http\Response
     */
    public function edit(Score $score)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Score $score
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Score $score)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Score $score
     * @return \Illuminate\Http\Response
     */
    public function destroy(Score $score)
    {
        //
    }
}
