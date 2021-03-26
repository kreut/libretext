<?php

namespace App\Http\Controllers;

use App\AssignToPresetGroup;
use App\AssignToPresetTiming;
use App\Course;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AssignToPresetController extends Controller
{

    public function index(Request $request, Course $course)
    {

        $response['type'] = 'error';
        /*  * $authorized = Gate::inspect('store', $course);
          *
          * if (!$authorized->allowed()) {
          * $response['message'] = $authorized->message();
          * return $response;
          * }**/

        $enrollments = $course->enrollments;
        $users_by_id = [];
        foreach ($enrollments as $enrollment) {
            $users_by_id[$enrollment->user_id] = "{$enrollment->first_name} {$enrollment->last_name}({$enrollment->email})";
        }
        $sections = $course->sections;
        $sections_by_id = [];
        foreach ($sections as $section) {
            $sections_by_id[$section->id] = $section->name;
        }
        try {
            $assignToPresetTimings = $course->assignToPresetTimings;
            $assign_to_presets = [];
            foreach ($assignToPresetTimings as $assignToPresetTiming) {
                $assign_to_preset_groups = AssignToPresetTiming::find($assignToPresetTiming->id)
                    ->assignToPresetGroups()
                    ->with('assignToPresetTimings')
                    ->get();

                $assign_to_preset['assign_to_preset_timing_id'] = $assignToPresetTiming->id;
                $assign_to_preset['due_time'] = $assignToPresetTiming->due_time;
                $assign_to_preset['day_of_week'] = $assignToPresetTiming->day_of_week;
                $assign_to_preset['groups'] = [];
                foreach ($assign_to_preset_groups as $assignToPresetGroup) {
                    $text = $this->getAssignToGroupText($assignToPresetGroup, $users_by_id, $sections_by_id, $course);
                    $assign_to_preset['groups'][] =
                        ['value' => ["{$assignToPresetGroup->group}_id" => $assignToPresetGroup->id],
                            'text' => $text];
                }
                $assign_to_presets[] = $assign_to_preset;
            }
            $response['type'] = 'success';
            $response['assign_to_presets'] = $assign_to_presets;
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your assign to presets for this course.  Please try again or contact us for assistance.";
        }

        return $response;


    }

    public function getAssignToGroupText(AssignToPresetGroup $assign_to_preset_group, array $users_by_id, array $sections_by_id, Course $course)
    {
        $text = "None";

        switch ($assign_to_preset_group['group']) {
            case('user'):
                $text = $users_by_id[$assign_to_preset_group->group_id];
                break;
            case('section'):
                $text = $sections_by_id[$assign_to_preset_group->group_id];
                break;
            case('course'):
                $text = $course->name;
        }
        return $text;
    }

    public function store(Request $request,
                          Course $course,
                          AssignToPresetTiming $assignToPresetTiming
    )
    {
        $response['type'] = 'error';
        /*  * $authorized = Gate::inspect('store', $course);
          *
          * if (!$authorized->allowed()) {
          * $response['message'] = $authorized->message();
          * return $response;
          * }**/


        $groups = $request->groups;
        try {
            DB::beginTransaction();
            $assignToPresetTiming->due_time = $request->due_time;
            $assignToPresetTiming->day_of_week = $request->day_of_week;
            $assignToPresetTiming->course_id = $course->id;
            $assignToPresetTiming->save();
            foreach ($groups as $group_info) {
                $assignToPresetGroup = new AssignToPresetGroup();
                $key = array_key_first($group_info['value']);
                $group = str_replace('_id', '', $key);
                $group_id = $group_info['value'][$key];
                $assignToPresetGroup->group = $group;
                $assignToPresetGroup->group_id = $group_id;
                $assignToPresetGroup->assign_to_preset_timing_id = $assignToPresetTiming->id;
                $assignToPresetGroup->save();
            }
            $response['type'] = 'success';
            $response['message'] = "Your Assign To preset has been saved.";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the assign to preset.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
