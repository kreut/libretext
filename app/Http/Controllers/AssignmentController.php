<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Traits\DateFormatter;
use App\Course;
use App\Score;
use App\Extension;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreAssignment;
use \Exception;
use App\Exceptions\Handler;
use \Illuminate\Http\Request;

class AssignmentController extends Controller
{
    use DateFormatter;

    /**
     *
     * Display all assignments for the course
     * @param Course $course
     * @param Assignment $assignment
     * @return mixed
     */
    public function index(Course $course)
    {

        $authorized = Gate::inspect('view', $course);
        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignments = $course->assignments;
            foreach ($course->assignments as $key => $assignment) {
                $assignments[$key]['credit_given_if_at_least'] = "{$assignment['num_submissions_needed']} questions are {$assignment['type_of_submission']}";

                $assignments[$key]['available_from_date'] = $this->getDateFromSqlTimestamp($assignment['available_from']);
                $assignments[$key]['available_from_time'] = $this->getTimeFromSqlTimestamp($assignment['available_from']);

                $assignments[$key]['due_date'] = $this->getDateFromSqlTimestamp($assignment['due']);
                $assignments[$key]['due_time'] = $this->getTimeFromSqlTimestamp($assignment['due']);

            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your assignments.  Please try again by refreshing the page or contact us for assistance.";
        return $response;
        }
        return $assignments;
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
                    'available_from' => $data['available_from_date'] . ' ' . $data['available_from_time'],
                    'due' => $data['due_date'] . ' ' . $data['due_time'],
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
     *
     * Display the specified resource
     *
     * @param Assignment $assignment
     * @return Assignment
     */
    public function show($id)
    {
        return Assignment::find($id);

    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Assignment $assignment
     * @return \Illuminate\Http\Response
     */
    public function update(StoreAssignment $request, Assignment $assignment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $data = $request->validated();
            $data['available_from'] = $data['available_from_date'] . ' ' . $data['available_from_time'];

            $data['due'] = $data['due_date'] . ' ' . $data['due_time'];

            //remove what's not needed
            foreach (['available_from_date', 'available_from_time', 'due_date', 'due_time'] as $value) {
                unset($data[$value]);
            }
            $assignment->update($data);
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$assignment->name</strong> has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating <strong>$course->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     *
     * Delete an assignment
     *
     * @param Course $course
     * @param Assignment $assignment
     * @param Score $score
     * @return mixed
     * @throws Exception
     */
    public function destroy(Assignment $assignment)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('delete', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::transaction(function () use ($assignment) {
                DB::table('assignment_question')->where('assignment_id', '=', $assignment->id)->delete();
                DB::table('extensions')->where('assignment_id', '=', $assignment->id)->delete();
                DB::table('scores')->where('assignment_id', '=', $assignment->id)->delete();
                $assignment->delete();
            });
            $response['type'] = 'success';
            $response['message'] = "The assignment <strong>$assignment->name</strong> has been deleted.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing <strong>$assignment->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
