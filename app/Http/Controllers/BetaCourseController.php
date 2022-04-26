<?php

namespace App\Http\Controllers;

use App\BetaCourse;
use App\Course;
use App\Exceptions\Handler;
use App\Http\Requests\UntetherBetaCourse;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BetaCourseController extends Controller
{
    /**
     * @param Course $beta_course
     * @return array
     * @throws Exception
     */
    public function getAlphaCourseFromBetaCourse(Course $beta_course): array
    {
        try {
            $response['type'] = 'error';
            $beta_course = DB::table('beta_courses')->where('id', $beta_course->id)->first();
            if (!$beta_course){
                throw new Exception("No Alpha course associated with Beta course $beta_course->id");
            }
            $response['alpha_course'] = Course::find($beta_course->alpha_course_id);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the Alpha course associated with this Beta course.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @return Application|ResponseFactory|Response
     */
    public function doNotShowBetaCourseDatesWarning()
    {
        $cookie = cookie()->forever('show_beta_course_dates_warning', 0);
        $response['type'] = 'success';
        return response($response)->withCookie($cookie);
    }

    /**
     * @param Course $course
     * @param BetaCourse $betaCourse
     * @return array
     * @throws Exception
     */
    public function getTetheredToAlphaCourse(Course $course, BetaCourse $betaCourse)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getTetheredToAlphaCourse', [$betaCourse, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $tethered_to_alpha_course = DB::table('beta_courses')->
            join('courses', 'alpha_course_id', '=', 'courses.id')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->select('courses.name AS course_name',
                    DB::raw('CONCAT(first_name, " " , last_name) AS instructor'))
                ->where('beta_courses.id', $course->id)
                ->first();
            if ($tethered_to_alpha_course) {
                $tethered_to_alpha_course_with_instructor_name = "$tethered_to_alpha_course->course_name ($tethered_to_alpha_course->instructor)";
            }
            $response['tethered_to_alpha_course'] = $tethered_to_alpha_course->course_name ?? '';
            $response['tethered_to_alpha_course_with_instructor_name'] = $tethered_to_alpha_course_with_instructor_name ?? '';
            $response['beta_course'] = $course->name;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the Beta courses.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    public function getBetaCoursesFromAlphaCourse(Course $alpha_course, BetaCourse $betaCourse)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getBetaCoursesFromAlphaCourse', [$betaCourse, $alpha_course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['beta_courses'] = $alpha_course->betaCoursesInfo();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the Beta courses.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    /**
     * @param UntetherBetaCourse $request
     * @param Course $course
     * @param BetaCourse $betaCourse
     * @return array
     * @throws Exception
     */
    public function untetherBetaCourseFromAlphaCourse(UntetherBetaCourse $request, Course $course, BetaCourse $betaCourse): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('untetherBetaCourseFromAlphaCourse', [$betaCourse, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $request->validated();
        try {
            $alpha_course = DB::table('beta_courses')
                ->where('beta_courses.id', $course->id)
                ->join('courses', 'beta_courses.alpha_course_id', '=', 'courses.id')
                ->select('courses.name')
                ->pluck('name')
                ->first();
            $betaCourse->where('id', $course->id)->delete();
            $response['message'] = "This course has been untethered from $alpha_course.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error untethering this Beta course.  Please try again or contact us for assistance.";
        }

        return $response;
    }

}
