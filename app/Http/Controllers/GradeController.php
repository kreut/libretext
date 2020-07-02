<?php

namespace App\Http\Controllers;

use App\Grade;
use App\Course;
use App\Enrollment;
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
     * @param \Illuminate\Http\Request $request
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
        foreach ($course->enrolledUsers as $key => $user) {
            $enrolled_users[$user->id] = $user->name;
        }

        //get all assignments in the course
        $assignments = $course->assignments;//get all the info

        $grades = $course->grades;

        //organize the grades by user_id and assignment
        $grades_by_user_and_assignment = [];
        foreach ($grades as $grade) {
            $grades_by_user_and_assignment[$grade->user_id][$grade->assignment_id] = $grade->grade;
        }

        //now fill in the actual grades
        $grades = [];
        foreach ($enrolled_users as $user_id => $name) {
            $user_grades = [];
            foreach ($assignments as $assignment) {
                $user_grades[$assignment->id] = $grades_by_user_and_assignment[$user_id][$assignment->id] ?? null;
            }
            $grades[] = array_merge(['user_id' => $user_id, 'name' => $name], ['grades' => $user_grades]);

        }

        return compact('grades', 'assignments');

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Grade $grade
     * @return \Illuminate\Http\Response
     */
    public function edit(Grade $grade)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Grade $grade
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Grade $grade)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Grade $grade
     * @return \Illuminate\Http\Response
     */
    public function destroy(Grade $grade)
    {
        //
    }
}
