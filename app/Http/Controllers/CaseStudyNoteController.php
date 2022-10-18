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
     * @param Assignment $assignment
     * @param CaseStudyNote $caseStudyNote
     * @return array
     * @throws Exception
     */
    public function resetAssignmentNotes(Assignment $assignment, CaseStudyNote $caseStudyNote): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('resetAssignmentNotes', [$caseStudyNote, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            DB::beginTransaction();
            DB::table('case_study_notes')->where('assignment_id', $assignment->id)->delete();
            DB::table('patient_informations')->where('assignment_id', $assignment->id)->delete();
            DB::commit();
            $response['type'] = 'info';
            $response['message'] = 'The Case Study Notes have been reset.';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating resetting the Case Study Notes. Please try again or contact us for assistance.";
        }
        return $response;
    }

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
            DB::beginTransaction();
            $new_notes = CaseStudyNote::updateOrCreate(
                ['assignment_id' => $assignment->id,
                    'type' => $data['type'],
                    'version' => $request->version],
                ['text' => $request->text]
            );
            DB::commit();
            $response['new_notes'] = $new_notes;
            $response['type'] = 'success';
            $response['message'] = "Your notes have been updated.";
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the case study notes. Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Assignment $assignment
     * @param CaseStudyNote $caseStudyNote
     * @return array
     * @throws Exception
     */
    public function show(Assignment $assignment, CaseStudyNote $caseStudyNote): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('show', [$caseStudyNote, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $case_study_notes = $caseStudyNote->where('assignment_id', $assignment->id)
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

    public function destroy(CaseStudyNote $caseStudyNote): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', $caseStudyNote);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $formatted_type = $caseStudyNote->formatType($caseStudyNote->type);
        try {
            switch ($caseStudyNote->version) {
                case(0):
                    $assignment_id = $caseStudyNote->assignment_id;
                    $all_notes = DB::table('case_study_notes')->where('assignment_id', $assignment_id)
                        ->where('type', $caseStudyNote->type)
                        ->get();
                    foreach ($all_notes as $notes) {
                        DB::table('assignment_question_case_study_notes')
                            ->where('case_study_notes_id', $notes->id)
                            ->delete();
                        DB::table('case_study_notes')
                            ->where('id', $notes->id)
                            ->delete();
                    }
                    $message = "The Initial Conditions and the Updated Information for $formatted_type have both been deleted.";
                    break;
                case(1):
                    DB::table('assignment_question_case_study_notes')
                        ->where('case_study_notes_id', $caseStudyNote->id)
                        ->delete();
                    $caseStudyNote->delete();
                    $message = "The Updated Information for $formatted_type has been deleted.";
                    break;
                default:
                    $message = "Invalid type for Case Study Notes";
            }
            $response['message'] = $message;
            $response['type'] = 'info';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting your case study notes. Please try again or contact us for assistance.";
        }
        return $response;
    }
}
