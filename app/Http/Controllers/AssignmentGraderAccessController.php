<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Course;
use App\AssignmentGraderAccess;
use App\User;
use Exception;
use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;


class AssignmentGraderAccessController extends Controller
{

    /**
     * @param Assignment $assignment
     * @param int $access_level
     * @return array
     * @throws Exception
     */
    public function updateAllGraders(Assignment $assignment,
                                     int $access_level,
                                     AssignmentGraderAccess $assignmentGraderAccess)
    {
        $response['type'] = 'error';


        $authorized = Gate::inspect('assignmentAccessForGraders', [$assignmentGraderAccess, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            DB::beginTransaction();
            foreach ($assignment->course->graders() as $grader) {
                $sync_data[$grader->id]['access_level'] = $access_level;
                $access_level === -1 ? $assignment->graders()->detach($grader->id)
                    : $assignment->graders()->syncWithoutDetaching($sync_data);

            }
            DB::commit();
            switch ($access_level) {
                case(1):
                    $message = "All of your graders now have full access to $assignment->name.";
                    $type = "success";
                    break;
                case(0):
                    $message = "None of your graders have access to $assignment->name.";
                    $type = "info";
                    break;
                case(-1):
                    $message = "Your graders now have their default section access to $assignment->name.";
                    $type = "success";
                    break;
                default:
                    $message = "Incorrect access level.";
                    $type = "info";
            }
            $response['message'] = $message;
            $response['type'] = $type;
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the access globally for $assignment->name.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Assignment $assignment
     * @param User $grader
     * @param int $access_level
     * @param AssignmentGraderAccess $assignmentGraderAccess
     * @return array
     * @throws Exception
     */
    public function updateGrader(Assignment $assignment,
                                 User $grader,
                                 int $access_level,
                                 AssignmentGraderAccess $assignmentGraderAccess)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('assignmentAccessForGrader', [$assignmentGraderAccess, $assignment, $grader]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {


            $sync_data[$grader->id]['access_level'] = $access_level;
            $access_level === -1
                ? $assignment->graders()->detach($grader->id)
                : $assignment->graders()->syncWithoutDetaching($sync_data);

            $response['type'] = $access_level ? 'success' : 'info';
            $grader_name = $grader->first_name . ' ' . $grader->last_name;
            switch ($access_level) {
                case(1):
                    $sections = ["All sections"];
                    $message = "$grader_name now has full access to $assignment->name.";
                    break;
                case(0):
                    $sections = ["No sections"];
                    $message = "$grader_name now has no access to $assignment->name.";
                    break;
                case(-1):
                    $sections_info = $assignment->course->graderSections($grader);
                    foreach ($sections_info as $section) {
                        $sections[] = $section->name;
                    }
                    $message = "$grader_name now has their default section access to $assignment->name.";
                    break;
            }
            $response['sections'] = $sections;
            $response ['message'] = $message;
            $response['access_level'] = $access_level;
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the access globally for $assignment->name.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Assignment $assignment
     * @param AssignmentGraderAccess $assignmentGraderAccess
     * @return array
     * @throws Exception
     */
    public function index(Assignment $assignment, AssignmentGraderAccess $assignmentGraderAccess)
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('index', [$assignmentGraderAccess, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            $response['assignment_grader_access'] = $assignment->gradersAccess();
            $response['type'] = 'success';

        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get the grader permissions.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;


    }
}
