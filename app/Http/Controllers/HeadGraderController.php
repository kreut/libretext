<?php

namespace App\Http\Controllers;

use App\Course;
use App\HeadGrader;
use App\User;
use Exception;
use App\Exceptions\Handler;
use Illuminate\Http\Request;

class HeadGraderController extends Controller
{
    /**
     * @param Request $request
     * @param Course $course
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function update(Request $request, Course $course, User $user)
    {

        $response['type'] = 'error';


        /**   $authorized = Gate::inspect('head', [$Grader, $course, $grader]);
         *
         * if (!$authorized->allowed()) {
         * $response['message'] = $authorized->message();
         * return $response;
         * }**/

        try {

            HeadGrader::updateOrCreate(
                ['course_id' => $course->id],
                ['user_id' => $user->id]);

            $response['message'] = "$user->first_name $user->last_name is now the Head Grader.";
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error setting this grader as the head grader.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public function destroy(Request $request, Course $course)
    {

        $response['type'] = 'error';


        /**   $authorized = Gate::inspect('head', [$Grader, $course, $grader]);
         *
         * if (!$authorized->allowed()) {
         * $response['message'] = $authorized->message();
         * return $response;
         * }**/

        try {

            $course->headGrader()->delete();
            $response['message'] = "You have removed the Head Grader from the course.";
            $response['type'] = 'info';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the head grader.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;

    }
}
