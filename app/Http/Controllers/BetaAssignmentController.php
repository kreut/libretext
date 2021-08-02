<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class BetaAssignmentController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $alpha_assignment
     * @return array
     * @throws Exception
     */
    public function getBetaCourseFromAlphaAssignment(Request $request, Assignment $alpha_assignment): array
    {
        $response['type'] = 'error';

        try {
            if (!$request->user()) {
                $response['type'] = 'success';
                $response['login_redirect'] = true;
                return $response;
            }
            $beta_assignment_id = false;
            $beta_assignments_info = DB::table('beta_assignments')
                ->join('assignments', 'beta_assignments.id', '=', 'assignments.id')
                ->where('alpha_assignment_id', $alpha_assignment->id)
                ->select('beta_assignments.id', 'assignments.course_id')
                ->get();
            foreach ($beta_assignments_info as $beta_assignment_info) {
                $beta_assignments_by_course_id[$beta_assignment_info->course_id] = $beta_assignment_info->id;
            }

            $possible_courses = [];
            switch ($request->user()->role) {
                case(3):
                    $possible_courses = DB::table('enrollments')
                        ->join('courses', 'enrollments.course_id', '=', 'courses.id')
                        ->where('enrollments.user_id', $request->user()->id)
                        ->select('course_id')
                        ->orderBy('courses.start_date', 'desc')
                        ->pluck('course_id')
                        ->toArray();
                    break;
                case(2):
                    $possible_courses = DB::table('courses')
                        ->where('user_id', $request->user()->id)
                        ->select('id AS course_id')
                        ->orderBy('courses.start_date', 'desc')
                        ->pluck('course_id')
                        ->toArray();
                    break;
            }
            foreach ($possible_courses as $course_id) {
                if (isset($beta_assignments_by_course_id[$course_id])) {
                    $beta_assignment_id = $beta_assignments_by_course_id[$course_id];
                    break;
                }
            }
            $response['beta_assignment_id'] = $beta_assignment_id;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error checking if this is a Beta assignment.  Please try again or contact us for assistance.";
        }

        return $response;

    }
}
