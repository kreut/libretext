<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Traits\IframeFormatter;
use App\Traits\LibretextFiles;
use Exception;
use Illuminate\Support\Facades\DB;

class BetaCourseApprovalController extends Controller
{
    use LibretextFiles;
    use IframeFormatter;

    /**
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function getByAssignment(Assignment $assignment)
    {

        $response['type'] = 'error';
        $beta_course_approvals = [];
        try {
            $beta_course_add_approvals = DB::table('beta_course_approvals')
                ->join('assignment_question', 'beta_course_approvals.alpha_assignment_question_id', '=', 'assignment_question.id')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->select('alpha_assignment_question_id',
                    'questions.id AS question_id',
                    'title',
                    'library',
                    'page_id',
                    'non_technology',
                    'technology_iframe')
                ->where('beta_assignment_id', $assignment->id)
                ->get();

            $beta_course_remove_approvals = DB::table('beta_course_approvals')
                ->join('questions', 'beta_course_approvals.beta_question_id', '=', 'questions.id')
                ->select('alpha_assignment_question_id',
                    'questions.id AS question_id',
                    'title',
                    'library',
                    'page_id',
                    'non_technology',
                    'technology_iframe')
                ->where('beta_assignment_id', $assignment->id)
                ->get();


            foreach ($beta_course_add_approvals as $key => $beta_course_approval) {
                $beta_course_approvals[$key] = $beta_course_approval;
                $beta_course_approvals[$key]->non_technology_iframe_src = $this->getLocallySavedPageIframeSrc((array)$beta_course_approval);
                $beta_course_approvals[$key]->technology_iframe = $this->formatIframeSrc($beta_course_approval->technology_iframe, '');
                $beta_course_approvals[$key]->action = 'add';
            }

            foreach ($beta_course_remove_approvals as $key => $beta_course_approval) {
                $beta_course_approvals[$key] = $beta_course_approval;
                $beta_course_approvals[$key]->non_technology_iframe_src = $this->getLocallySavedPageIframeSrc((array)$beta_course_approval);
                $beta_course_approvals[$key]->technology_iframe = $this->formatIframeSrc($beta_course_approval->technology_iframe, '');
                $beta_course_approvals[$key]->action = 'remove';
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
