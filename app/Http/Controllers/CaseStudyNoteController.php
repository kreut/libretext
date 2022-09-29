<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\CaseStudyNote;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CaseStudyNoteController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param CaseStudyNote $caseStudyNote
     * @return array
     * @throws Exception
     */
    public function show(Request $request, Assignment $assignment, CaseStudyNote $caseStudyNote): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('show', [$caseStudyNote, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['case_study_notes'] = $caseStudyNote->where('user_id', request()->user()->id)
                ->where('assignment_id', $assignment->id)
                ->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your case study notes. Please try again or contact us for assistance.";
        }
        return $response;
    }
}
