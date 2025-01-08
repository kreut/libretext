<?php

namespace App\Http\Controllers;

use App\CanvasAPI;
use App\Course;
use App\Exceptions\Handler;
use App\Jobs\ProcessUpdateCanvasAssignments;
use App\LmsAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;


class CanvasAPIController extends Controller
{

    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function validateCourseUrl(Request $request)
    {
        try {
            $response['type'] = 'error';
            $course = Course::find($request->course_id);
            $url = $request->url;
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                $response['message'] = "{$request->url} is not a valid URL.";
                return $response;
            }
            $path = parse_url($url, PHP_URL_PATH);

            $segments = explode('/', rtrim($path, '/'));
            $lms_course_id = end($segments);
            $lmsApi = new LmsAPI();
            $lms_result = $lmsApi->getCourse($course->getLtiRegistration(),
                $course->user_id, $lms_course_id);

            if ($lms_result['type'] === 'error') {
                $response['message'] = $lms_result['message'];
            } else {
                $response['lms_course_name'] = $lms_result['message']->name;
                $response['lms_course_id'] = $lms_result['message']->id;
                $response['type'] = 'success';
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error validating this Canvas course URL.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Course $course
     * @param string $property
     * @return array
     * @throws Exception
     */
    public function alreadyUpdated(Course $course, string $property)
    {

        $response['type'] = 'error';
        try {
            if ($course->user_id !== request()->user()->id) {
                $response['message'] = 'You are not allowed to update these Canvas assignments.';
                return $response;
            }
            if (!in_array($property, ['everybodys', 'points'])) {
                $response['message'] = "$property is not a valid property.";
                return $response;
            }

            DB::table('canvas_updates')->updateOrInsert([
                'course_id' => $course->id],
                ["updated_$property" => 1, 'updated_at' => now()]);
            $response['type'] = 'info';
            $response['message'] = "Your response has been saved.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error showing that you have already updated ths $property for the Canvas course.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Course $course
     * @param string $property
     * @return array
     * @throws Exception
     */
    public function updateCanvasAssignments(Course $course, string $property): array
    {
        $response['type'] = 'error';
        if ($course->user_id !== request()->user()->id) {
            $response['message'] = 'You are not allowed to update these Canvas assignments.';
            return $response;
        }
        try {
            ProcessUpdateCanvasAssignments::dispatch($course, $property);

            $response['type'] = 'info';
            $response['message'] = $property === 'points'
                ? "Depending on your class size, this may take a few minutes. You will receive an email with an updated status of the process."
                : "The updates may take up to a a minute.  You will receive an email with an updated status of the process.";

            return $response;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the Canvas assignments.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
