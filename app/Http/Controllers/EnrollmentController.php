<?php

namespace App\Http\Controllers;

use App\Enrollment;
use App\Course;

use App\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreEnrollment;
use App\Exceptions\Handler;
use \Exception;

class EnrollmentController extends Controller
{

    public function index(Request $request, Enrollment $enrollment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $enrollment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $response['enrollments'] = DB::table('courses')
                ->join('sections', 'courses.id', '=', 'sections.course_id')
                ->join('enrollments', 'sections.id', '=', 'enrollments.section_id')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('enrollments.user_id', '=', $request->user()->id)
                ->where('courses.shown', 1)
                ->select(DB::raw('CONCAT(first_name, " " , last_name) AS instructor'),
                    DB::raw('CONCAT(courses.name, " - " , sections.name) AS course_section_name'),
                    'courses.start_date',
                    'courses.end_date',
                    'courses.id')
                ->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your enrollments.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    /**
     * @param StoreEnrollment $request
     * @param Enrollment $enrollment
     * @param Section $Section
     * @return array
     * @throws Exception
     */
    public function store(StoreEnrollment $request, Enrollment $enrollment, Section $Section)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $enrollment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $data = $request->validated();;
            $section = $Section->where('access_code', '=', $data['access_code'])->first();
            if ($section->course->enrollments->isNotEmpty()) {
                $enrolled_user_ids = $section->course->enrollments->pluck('user_id')->toArray();
                if (in_array($request->user()->id, $enrolled_user_ids)) {
                    $response['message'] = 'You are already enrolled in another section of this course.';
                    return $response;
                }
            }
            $course_id = $section->course_id;
            $section_id = $section->id;

            //make sure they don't sign up twice!
            $response['validated'] = true;
            $course_section_name = "{$section->course->name} - {$section->name}";

            if (Enrollment::where('user_id', $request->user()->id)->where('section_id', $section_id)->get()->isNotEmpty()) {
                $response['type'] = 'error';
                $response['message'] = "You are already enrolled in <strong>$course_section_name</strong>.";
            } else {
                $enrollment->user_id = $request->user()->id;
                $enrollment->section_id = $section_id;
                $enrollment->course_id = $course_id;
                $enrollment->save();
                $response['type'] = 'success';
                $response['message'] = "You are now enrolled in <strong>$course_section_name</strong>.";
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error enrolling you in the course.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}

