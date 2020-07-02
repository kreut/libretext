<?php

namespace App\Http\Controllers;

use App\Grade;
use App\Course;
use Illuminate\Http\Request;

class GradeController extends Controller
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     *
     * Show the grades for a given course
     *
     * @param Course $course
     * @return array
     */
    public function show(Course $course)
    {
        //get all user_ids for the user enrolled in the course
        $enrolled_users = $course->enrollments->pluck('user_id')->toArray();
        //get all assignments in the course
        $assignments = $course->assignments;//get all the info
        $grades = $course->grades;

        //organize the grades by user_id and assignment
        $grades_by_user_and_assignment = [];
        foreach ($grades as $grade){
            $grades_by_user_and_assignment[$grade->user_id][$grade->assignment_id] = $grade->grade;
        }

        //now fill in the actual grades
        $all_grades = [];
        foreach ($enrolled_users as $enrolled_user){
            foreach ($assignments as $assignment){
                $all_grades[$enrolled_user][$assignment->id] = $grades_by_user_and_assignment[$enrolled_user][$assignment->id] ?? null;
            }
        }

       return $all_grades;

        //get all grades in the course and make the keys the user, and the next level the assignment key
        //foreach user, loop through the assignments and add if they exist


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Grade  $grade
     * @return \Illuminate\Http\Response
     */
    public function edit(Grade $grade)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Grade  $grade
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Grade $grade)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Grade  $grade
     * @return \Illuminate\Http\Response
     */
    public function destroy(Grade $grade)
    {
        //
    }
}
