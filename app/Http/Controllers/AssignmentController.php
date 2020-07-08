<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prophecy\Doubler\Generator\ClassCodeGenerator;
use App\Http\Requests\StoreAssignment;

class AssignmentController extends Controller
{

    public function getDateFromSqlTimestamp(string $date) {
        return date('Y-m-d', strtotime($date));

    }

    public function getTimeFromSqlTimestamp(string $date) {
        return date('H:i:00', strtotime($date));

    }
    /**
     *
     * Display all assignments for the course
     * @param Course $course
     * @param Assignment $assignment
     * @return mixed
     */
    public function index(Course $course, Assignment $assignment)
    {
        $assignments = $assignment->where('course_id', '=', $course->id)
            ->orderBy('due_date', 'asc')
            ->get();
        foreach ($assignments as $key => $assignment) {
            $assignments[$key]['credit_given_if_at_least'] = "{$assignment['num_submissions_needed']} questions are {$assignment['type_of_submission']}";

            $assignments[$key]['available_on_date'] = $this->getDateFromSqlTimestamp($assignment['available_on']);
            $assignments[$key]['available_on_time'] = $this->getTimeFromSqlTimestamp($assignment['available_on']);

            $assignments[$key]['due_date'] = $this->getDateFromSqlTimestamp($assignment['due_date']);
            $assignments[$key]['due_time'] = $this->getTimeFromSqlTimestamp($assignment['due_date']);

        }
        return $assignments;
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
    public function store(StoreAssignment $request, Course $course, Assignment $assignment)
    {

        $response['type'] = 'error';
        try {
            $data = $request->validated();
            $assignment->create(
                ['name' => $data['name'],
                    'available_on' => $data['available_on_date'] . ' ' . $data['available_on_time'],
                    'due_date' => $data['due_date'] . ' ' . $data['due_time'],
                    'num_submissions_needed' => $data['num_submissions_needed'],
                    'type_of_submission' => $data['type_of_submission'],
                    'course_id' => $course->id
                ]
            );
            $response['type'] = 'success';
            $response['message'] = "The assignment <strong>$request->assignment</strong> has been created.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating <strong>$request->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Assignment $assignment
     * @return \Illuminate\Http\Response
     */
    public function show(Assignment $assignment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Assignment $assignment
     * @return \Illuminate\Http\Response
     */
    public function edit(Assignment $assignment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Assignment $assignment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Assignment $assignment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Assignment $assignment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Assignment $assignment)
    {
        //
    }
}
