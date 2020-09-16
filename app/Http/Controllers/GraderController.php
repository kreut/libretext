<?php

namespace App\Http\Controllers;

use App\Course;
use App\Grader;
use App\User;
use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GraderController extends Controller
{
    public function getGradersByCourse(Request $request, Course $course, Grader $Grader)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getGraders', [$Grader, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['graders'] = [];
            foreach ($course->graders as $grader) {
                $response['graders'][] = [
                    'name' => $grader->first_name . ' ' . $grader->last_name,
                    'id' => $grader->id
                ];
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your graders.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;

    }

    public function removeGraderFromCourse(Request $request, Course $Course, User $User, Grader $Grader)
    {
        $response['type'] = 'error';
        $course = $Course::find($request->course->id);
        $student_user = $User::find($request->user->id);
        $authorized = Gate::inspect('removeGraderFromCourse', [$Grader, $course, $student_user]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $grader = $Grader
                ->where('user_id', $student_user->id)
                ->where('course_id', $course->id)
                ->first();
            $grader->delete();
            $response['type'] = 'success';
            $response['message'] = 'The grader has been removed from your course.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your graders.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;

    }
}
