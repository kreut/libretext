<?php

namespace App\Http\Controllers;

use App\Course;
use App\Exceptions\Handler;
use App\GraderNotification;
use App\Http\Requests\UpdateGraderNotification;
use Exception;
use Illuminate\Http\Request;

class GraderNotificationController extends Controller
{

    public function index(Request $request, Course $course)
    {
        $response['type'] = 'error';


        /**   $authorized = Gate::inspect('head', [$Grader, $course, $grader]);
         *
         * if (!$authorized->allowed()) {
         * $response['message'] = $authorized->message();
         * return $response;
         * }**/

        try {

            $grader_notifications = $course->graderNotifications;

            $response['grading_notifications'] = $grader_notifications;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error setting this grader as the head grader.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;

    }

    public function update(UpdateGraderNotification $request, Course $course)
    {

        $response['type'] = 'error';

        $request->validated();

        /**   $authorized = Gate::inspect('head', [$Grader, $course, $grader]);
         *
         * if (!$authorized->allowed()) {
         * $response['message'] = $authorized->message();
         * return $response;
         * }**/

        try {

            GraderNotification::updateOrCreate(
                ['course_id' =>$course->id],
                $request->all());
            $response['type'] = 'success';
            $response['message'] = 'Your Grader Notifications have been updated.';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error setting updating the grader notifications.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;

    }


}
