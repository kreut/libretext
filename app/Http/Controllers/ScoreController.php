<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Score;
use App\Course;
use App\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
        $extensions = $course->extensions;
        foreach ($extensions as $value) {
            $extension[$value->user_id][$value->assignment_id] = 'Extension';
        }


        //organize the scores by user_id and assignment
        $scores_by_user_and_assignment = [];
        foreach ($scores as $score) {
            $scores_by_user_and_assignment[$score->user_id][$score->assignment_id]['score'] = $score->score;
        }

        //now fill in the actual scores
        $rows = [];
        foreach ($enrolled_users as $user_id => $name) {
            $columns = [];

            foreach ($assignments as $assignment) {
                $score = $scores_by_user_and_assignment[$user_id][$assignment->id] ?? '-';
                $score = $extension[$user_id][$assignment->id] ?? '-';
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

    public function updateScorePolicy(int $course_id, int $assignment_id, int $student_user_id)
    {
        //validate that they are the owner of the course
        $is_owner_of_course = DB::table('courses')
            ->select('id')
            ->where('id', '=', $course_id)
            ->where('user_id', '=', Auth::user()->id)
            ->first();
        //validate that the assignment is in the course
        $assignment_is_in_course = DB::table('assignments')
            ->select('id')
            ->where('id', '=', $assignment_id)
            ->where('course_id', '=', $course_id)
            ->first();
        //validate that the student is enorolled in the course
        $student_is_in_enrolled_in_the_course = DB::table('enrollments')
            ->select('user_id')
            ->where('course_id', '=', $course_id)
            ->where('user_id', '=', $student_user_id)
            ->first();

        return ($is_owner_of_course && $assignment_is_in_course && $student_is_in_enrolled_in_the_course);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Score $score
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request)
    {
        /*
         *
        //start: 1. test the auth logic --- works
        2. Move into a policy
        4. change the grade with js
        5. do the due date version
        6. lock down the assignments
        7. Upload all to dev
        8. Move all to other mac
    */
        $response['type'] = 'error';
        try {
            if (!$this->updateScorePolicy($request->course_id, $request->assignment_id, $request->student_user_id)) {
                $response['message'] = "You don't have access to that student/assignment combination.";
                return $response;
            }

            //todo: validate the data as a possible score (completed or not
            Score::updateOrCreate(
                ['user_id' => $request->student_user_id, 'assignment_id' => $request->assignment_id],
                ['score' => $request->score]
            );

            $response['type'] = 'success';
            $response['message'] = 'The score has been updated.';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the score.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Score $score
     * @return \Illuminate\Http\Response
     */
    public
    function destroy(Score $score)
    {
        //
    }
}
