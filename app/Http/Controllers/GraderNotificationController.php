<?php

namespace App\Http\Controllers;


use App\Course;
use App\Exceptions\Handler;
use App\Grader;
use App\GraderNotification;
use App\Http\Requests\UpdateGraderNotification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;


class GraderNotificationController extends Controller
{

    /**
     * @param Course $course
     * @param GraderNotification $graderNotification
     * @return array
     * @throws Exception
     */
    public function index(Course $course, GraderNotification $graderNotification): array
    {
        $response['type'] = 'error';


         $authorized = Gate::inspect('index', [$graderNotification, $course]);

         if (!$authorized->allowed()) {
          $response['message'] = $authorized->message();
          return $response;
          }

        try {

            $grader_notifications = $course->graderNotifications;

            $response['grading_notifications'] = $grader_notifications;
            $response['head_grader'] = $course->headGrader !== null;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error setting this grader as the head grader.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param UpdateGraderNotification $request
     * @param Course $course
     * @param GraderNotification $graderNotification
     * @return array
     * @throws Exception
     */
    public function update(UpdateGraderNotification $request, Course $course, GraderNotification $graderNotification): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$graderNotification, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $request->validated();

        try {

            GraderNotification::updateOrCreate(
                ['course_id' => $course->id],
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
