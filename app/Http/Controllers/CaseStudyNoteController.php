<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\CaseStudyNote;
use App\Exceptions\Handler;
use App\Http\Requests\UpdateCaseStudyNotes;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CaseStudyNoteController extends Controller
{

    /**
     * @param UpdateCaseStudyNotes $request
     * @param CaseStudyNote $caseStudyNote
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function update(UpdateCaseStudyNotes $request, CaseStudyNote $caseStudyNote, Assignment $assignment): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('show', [$caseStudyNote, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $data = $request->validated();
            $case_study_notes = json_decode($data['case_study_notes'], 1);
            DB::beginTransaction();
            foreach ($case_study_notes as $value) {
                CaseStudyNote::updateOrCreate(
                    ['user_id' => $request->user()->id,
                        'assignment_id' => $assignment->id,
                        'type' => $value['type'],
                        'identifier' => $value['identifier'] ?? null],
                    ['notes' => $value['notes']
                    ]);
            }
            DB::commit();
            $response['type'] = 'success';
            $resopnse['message'] = "Your notes have been updated.";
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the case study notes. Please try again or contact us for assistance.";
        }
        return $response;


    }

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
            $case_study_notes = $caseStudyNote->where('user_id', request()->user()->id)
                ->where('assignment_id', $assignment->id)
                ->get();
            if ($case_study_notes->isNotEmpty()) {
                foreach ($case_study_notes as $key => $value) {
                    $case_study_notes[$key]['expanded'] = false;
                }
            }
            $response['case_study_notes'] = $case_study_notes;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your case study notes. Please try again or contact us for assistance.";
        }
        return $response;
    }
}
