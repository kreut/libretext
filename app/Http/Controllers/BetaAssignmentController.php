<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class BetaAssignmentController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $alpha_assignment
     * @return array
     * @throws Exception
     */
    public function getBetaCourseFromAlphaAssignment(Request $request, Assignment $alpha_assignment)
    {
        $response['type'] = 'error';

        try {
            $beta_assignment_id = false;
            $beta_assignments_info = DB::table('beta_assignments')
                ->join('assignments', 'beta_assignments.id', '=', 'assignments.id')
                ->where('alpha_assignment_id', $alpha_assignment->id)
                ->select('beta_assignments.id', 'assignments.course_id')
                ->get();
            foreach ($beta_assignments_info as $beta_assignment_info) {
                $beta_assignments_by_course_id[$beta_assignment_info->course_id] = $beta_assignment_info->id;
            }
            $enrollments = DB::table('enrollments')
                ->where('user_id', $request->user()->id)
                ->select('course_id')
                ->orderBy('updated_at', 'desc')
                ->get('course_id')
                ->pluck('course_id')
                ->toArray();
            foreach ($enrollments as $course_id) {
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
