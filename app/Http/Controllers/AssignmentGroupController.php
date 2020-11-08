<?php

namespace App\Http\Controllers;

use App\Course;
use App\AssignmentGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;


use App\Exceptions\Handler;
use \Exception;

class AssignmentGroupController extends Controller
{
    public function getAssignmentGroupsByCourse(Request $request, Course $course, AssignmentGroup $assignmentGroup)
    {
        $response['type'] = 'error';
        try {
            $response['assignment_groups'] = $assignmentGroup->where(function ($q) use ($course) {
                $q->where('user_id', 0)->orWhere(function($q2) use ($course) {
                    $q2->where('user_id', Auth::user()->id)->where('course_id', $course->id);
                });
            })->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your assignment groups.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
