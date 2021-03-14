<?php

namespace App\Http\Controllers;

use App\Course;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignToGroupController extends Controller
{
    public function assignToGroups(Request $request, Course $course)
    {
        $response['type'] = 'error';
        try {
            $courses_and_sections_info = DB::table('sections')
                ->join('enrollments', 'sections.id', '=', 'enrollments.section_id')
                ->join('users', 'enrollments.user_id', '=', 'users.id')
                ->where('sections.course_id', $course->id)
                ->select(DB::raw("CONCAT(users.first_name, ' ', users.last_name, ' --- ', email) AS user"), DB::raw('sections.name AS section'))
                ->orderBy('user')
                ->get();

            $users = [];
            $sections = [];
            foreach ($courses_and_sections_info as $info) {
                if ($info->user && !in_array($info->user, $users)) {
                    $users[] = $info->user;
                }
                if ($info->section && !in_array($info->section, $sections)) {
                    $sections[] = $info->section;
                }
            }
            sort($sections);
            $response['assign_to_groups'] = array_merge(['Everybody'],
                $sections,
                $users);
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get the assign to groups for this course.  Please try again or contact us for assistance.";
        }
        return $response;


    }
}
