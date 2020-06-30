<?php

namespace App\Http\Controllers;

use App\Course;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCourse;
use Illuminate\Support\Facades\DB;
use App\Exceptions\Handler;
use \Exception;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        //todo it gets courses in descending order
        return response(DB::table('courses')
            ->where('user_id', auth()->user()->id)
            ->orderBy('start_date', 'desc')
            ->get());
    }

    /**
     *
     * Store a newly created resource in storage.
     *
     * @param StoreCourse $request
     * @param Course $course
     * @return mixed
     * @throws Exception
     */

    public function store(StoreCourse $request, Course $course)
    {
        //todo: check the validation rules
        $response['type'] = 'error';
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->user()->id;

            $course->create($data);
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$request->name</strong> has been created.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating <strong>$request->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     *
     * Update the specified resource in storage.
     *
     *
     * @param StoreCourse $request
     * @param Course $course
     * @return mixed
     * @throws Exception
     */
    public function update(StoreCourse $request, Course $course)
    {
        $response['type'] = 'error';
        try {
            $request->validated();
            $course->update($request->except('user_id'));
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$course->name</strong> has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating <strong>$course->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     *
     * Remove the specified resource from storage.
     *
     *
     * @param Course $course
     * @param Request $request
     * @return mixed
     * @throws Exception
     */
    public function destroy(Course $course, Request $request)
    {
        $response['type'] = 'error';
        try {
            $course->delete();
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$course->name</strong> has been deleted.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing <strong>$course->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
