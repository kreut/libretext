<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\BetaAssignment;
use App\Question;
use App\QuestionRevision;
use Illuminate\Support\Facades\Gate;
use App\BetaCourseApproval;
use App\Course;
use App\Exceptions\Handler;
use App\Traits\IframeFormatter;
use App\Traits\LibretextFiles;
use Exception;
use Illuminate\Support\Facades\DB;

class BetaCourseApprovalController extends Controller
{
    use LibretextFiles;
    use IframeFormatter;


    public function getByCourse(Course $course, BetaCourseApproval $betaCourseApproval)
    {
        $response['type'] = 'error';
         $authorized = Gate::inspect('getByCourse', [$betaCourseApproval, $course]);
         if (!$authorized->allowed()) {
             $response['message'] = $authorized->message();
             return $response;
         }

        $assignments = $course->assignments;
        if ($assignments) {
            $assignment_ids = $assignments->pluck('id')->toArray();
        }
        try {
            $pending_beta_course_approvals = $betaCourseApproval
                ->join('assignments', 'beta_course_approvals.beta_assignment_id', '=', 'assignments.id')
                ->whereIn('beta_assignment_id', $assignment_ids)
                ->select('assignments.id', 'name', DB::raw('count(*) as total_pending'))
                ->groupBy('assignments.id')
                ->get();
            $response['pending_beta_course_approvals'] = $pending_beta_course_approvals;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the Beta courses.  Please try again or contact us for assistance.";
        }

        return $response;
    }


    /**
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function getByAssignment(Assignment $assignment): array
    {

        $response['type'] = 'error';

        try {
            $beta_course_approvals = DB::table('beta_course_approvals')
                ->join('questions', 'beta_course_approvals.beta_question_id', '=', 'questions.id')
                ->select('questions.id AS question_id',
                    'questions.id AS id',
                    'title',
                    'library',
                    'action',
                    'page_id',
                    'non_technology',
                    'technology_iframe')
                ->where('beta_assignment_id', $assignment->id)
                ->get();

            foreach ($beta_course_approvals as $key => $beta_course_approval) {
                $alpha_assignment_id =BetaAssignment::find($assignment->id)->alpha_assignment_id;
                $assignment_question = DB::table('assignment_question')
                    ->where('assignment_id', $alpha_assignment_id)
                    ->where('question_id', $beta_course_approval->question_id)
                    ->first();
                $question_revision_number = $assignment_question->question_revision_id
                    ? QuestionRevision::find($assignment_question->question_revision_id)->revision_number
                    : 0;

                $beta_course_approvals[$key]->non_technology_iframe_src = $this->getHeaderHtmlIframeSrc((array)$beta_course_approval, $question_revision_number);
                $beta_course_approvals[$key]->technology_iframe = $this->formatIframeSrc($beta_course_approval->technology_iframe, '');
            }
            $response['beta_course_approvals'] = $beta_course_approvals;
            $response['type'] = 'success';
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get your beta course approvals.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
