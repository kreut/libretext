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
            $sections_info = DB::table('sections')
                ->where('course_id', $course->id)
                ->select('name', 'id')
                ->orderBy('name')
                ->get();

            $user_info = DB::table('sections')
                ->join('enrollments', 'sections.id', '=', 'enrollments.section_id')
                ->join('users', 'enrollments.user_id', '=', 'users.id')
                ->where('sections.course_id', $course->id)
                ->where('fake_student',0)
                ->select(DB::raw("CONCAT(users.first_name, ' ', users.last_name, ' (', email,')') AS name"), 'users.id as id')
                ->orderBy('name')
                ->get();
            $sections = [];
            foreach ($sections_info as $section) {
                array_push($sections, ['value' => ['section_id' => $section->id], 'text' => $section->name]);
            }
            $users = [];
            foreach ($user_info as $user){
                array_push($users, ['value' => ['user_id' => $user->id], 'text' => $user->name]);
            }

            $response['course'] =  ['value' => ['course_id' => $course->id], 'text' => 'Everybody'];
            $response['sections'] = $sections;
            $response['users']= $users;
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
