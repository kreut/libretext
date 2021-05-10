<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Course;
use App\GraderPermission;
use App\User;
use Exception;
use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;


class GraderPermissionController extends Controller
{
    /**
     * @param Course $course
     * @param string $type
     * @return array
     * @throws Exception
     */
    public function courseAccess(Course $course,
                                 string $type)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('courseAccessForGraders', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            DB::beginTransaction();
            foreach ($course->assignments as $assignment) {
                foreach ($course->graders() as $grader) {
                    $type
                        ? $assignment->graders()->syncWithoutDetaching($grader)
                        : $assignment->graders()->detach($grader);
                }
            }
            DB::commit();
            $response['type'] = $type ? 'success' : 'info';
            $message = $type
                ? 'All of your graders now have access to all of your assignments.'
                : 'None of your graders have any access to your assignments.';
            $response['message'] = $message;
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the access globally for the course.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;


    }

    public function assignmentAccess(Assignment $assignment,
                                     string $type)
    {
        $response['type'] = 'error';


        $authorized = Gate::inspect('assignmentAccessForGraders', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            DB::beginTransaction();
            foreach ($assignment->course->graders() as $grader) {
                $type
                    ? $assignment->graders()->syncWithoutDetaching($grader)
                    : $assignment->graders()->detach($grader);
            }
            DB::commit();
            $response['type'] = $type ? 'success' : 'info';
            $message = $type
                ? "All of your graders now have access to $assignment->name."
                : "None of your graders have any access to $assignment->name.";
            $response['message'] = $message;
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the access globally for $assignment->name.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;


    }


    public function update(Assignment $assignment,
                           User $user,
                           int $hasAccess)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('assignmentAccessForGrader', [$assignment, $user]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {


            !$hasAccess
                ? $assignment->graders()->syncWithoutDetaching($user)
                : $assignment->graders()->detach($user);

            $response['type'] = !$hasAccess ? 'success' : 'info';
            $access_message = !$hasAccess ? ' has access to ' : ' does not have access to ';
            $response['message'] = $user->first_name . ' ' . $user->last_name . $access_message . $assignment->name;
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the grader permissions.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;


    }


    public function index(Course $course, GraderPermission $graderPermission)
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('index', [$graderPermission, $course]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $response['graders'] = $course->graderInfo();
            $response['grader_permissions'] = array_values($course->graderPermissions());
            $response['type'] = 'success';


        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get the grader permissions.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;


    }
}
