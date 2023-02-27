<?php

namespace App\Http\Controllers;

use App\Course;
use App\AssignmentGroup;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAssignmentGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;


use App\Exceptions\Handler;
use \Exception;

class AssignmentGroupController extends Controller
{
    /**
     * @param AssignmentGroup $assignmentGroup
     * @return array
     * @throws Exception
     */
    public function getAssignmentGroupsByUser(AssignmentGroup $assignmentGroup)
    {
        $response['type'] = 'error';
        try {
            $default_assignment_groups = AssignmentGroup::where('user_id', 0)->select()->get();
            $user_assignment_groups = AssignmentGroup::where('user_id', Auth::user()->id)
                ->select()
                ->get();
            $response['assignment_groups'] = $assignmentGroup->combine($default_assignment_groups, $user_assignment_groups);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your assignment groups.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    public function store(StoreAssignmentGroup $request, Course $course, AssignmentGroup $assignmentGroup)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', [$assignmentGroup, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $data = $request->validated();

        try {
            $assignmentGroup->user_id = Auth::user()->id;
            $assignmentGroup->course_id = $course->id;
            $assignmentGroup->assignment_group = $data['assignment_group'];
            $assignmentGroup->save();
            $response['assignment_group_info'] = ['assignment_group_id' => $assignmentGroup->id,
                'assignment_group' => $data['assignment_group']];

            $response['message'] = "<strong>{$assignmentGroup->assignment_group}</strong> has been added as an assignment group.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the assignment group.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public function getAssignmentGroupsByCourseAndAssignment(Course $course): array
    {
        $response['type'] = 'error';
        try {
            $assignment_groups = DB::table('assignmentss')
                ->join('assignment_groups', 'assignments.assignment_group_id', '=', 'assignment_groups.id')
                ->where('assignments.course_id', $course->id)
                ->select('assignments.id AS assignment_id',
                    'assignments.name AS assignment_name',
                    'assignment_groups.id AS assignment_group_id',
                    'assignment_group')
                ->get();
            $response['assignment_groups'] = $assignment_groups;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }
        return $response;
    }


    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public function getAssignmentGroupsByCourse(Course $course): array
    {
        $response['type'] = 'error';
        try {
            $response['assignment_groups'] = $course->assignmentGroups();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your assignment groups.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public function getAssignmentGroupFilter(Request $request, Course $course): array
    {
        $response['type'] = 'error';
        try {
            $assignment_group_filter = null;
            if ($request->hasCookie('assignment_group_filter')) {
                $cookie = $request->cookie('assignment_group_filter');
                $assignment_group_filters_by_course = json_decode($cookie, true);
                $assignment_group_filter = $assignment_group_filters_by_course[$course->id] ?? null;
            }
            $response['assignment_group_filter'] = $assignment_group_filter;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
        return $response;
    }
}
