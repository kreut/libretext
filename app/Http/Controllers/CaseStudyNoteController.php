<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\CaseStudyNote;
use App\Exceptions\Handler;
use App\Http\Requests\UpdateCaseStudyNotes;
use App\PatientInformation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CaseStudyNoteController extends Controller
{
    /**
     * @param Request $request
     * @param CaseStudyNote $caseStudyNote
     * @param PatientInformation $patientInformation
     * @return array
     * @throws Exception
     */
    public function getUnsavedChanges(Request $request, CaseStudyNote $caseStudyNote, PatientInformation $patientInformation): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getUnsavedChanges', [$caseStudyNote, Assignment::find($request->assignment_id)]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $case_study_notes = $request->case_study_notes;
            $unsaved_case_study_notes = [];
            foreach ($case_study_notes as $case_study_note) {
                $case_study_notes_saved = DB::table('case_study_notes')
                    ->where('id', $case_study_note['id'])
                    ->where('text', $case_study_note['text'])
                    ->first();
                if (!$case_study_notes_saved) {
                    $case_study_note['type'] = $caseStudyNote->formatType(  $case_study_note['type']);
                    $unsaved_case_study_notes[] = $case_study_note;
                }
            }
            $common_question_text_saved = DB::table('assignments')
                ->where('id', $request->assignment_id)
                ->where('common_question_text', $request->common_question_text)
                ->exists();
            $current_patient_informations = DB::table('patient_informations')
                ->where('assignment_id', $request->assignment_id)
                ->first();
            $initial_patient_information_keys = $patientInformation->initialPatientInformationKeys();
            $initial_patient_information_saved = true;

            foreach ($initial_patient_information_keys as $key) {
                $current_patient_information = $current_patient_informations ? $current_patient_informations->{$key} : null;
                if ($request->patient_informations[$key] !== $current_patient_information) {
                    $initial_patient_information_saved = false;
                }
            }

            $updated_patient_information_saved = true;
            $updated_patient_information_keys = $patientInformation->updatedPatientInformationKeys();
            foreach ($updated_patient_information_keys as $key) {
                $current_patient_information = $current_patient_informations ? $current_patient_informations->{$key} : null;
                if ($request->patient_informations[$key] !== $current_patient_information) {
                    $updated_patient_information_saved = false;
                }
            }
            $response['unsaved_changes'] = [
                'unsaved_case_study_notes' => $unsaved_case_study_notes,
                'common_question_text_saved' => $common_question_text_saved,
                'initial_patient_information_saved' => $initial_patient_information_saved,
                'updated_patient_information_saved' => $updated_patient_information_saved
            ];
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the unsaved changes from your Case Study Notes. Please try again or contact us for assistance.";
        }
        return $response;
    }

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
     * @param Request $request
     * @param CaseStudyNote $caseStudyNote
     * @param PatientInformation $patientInformation
     * @return array
     * @throws Exception
     */
    public function saveAll(Request $request, CaseStudyNote $caseStudyNote, PatientInformation $patientInformation): array
    {
        $response['type'] = 'error';
        $assignment = Assignment::find($request->assignment_id);
        $authorized = Gate::inspect('saveAll', [$caseStudyNote, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            switch ($request->type) {
                case('initial conditions'):
                    $patient_information_keys = $patientInformation->initialPatientInformationKeys();
                    break;
                case('updated information'):
                    $patient_information_keys = $patientInformation->updatedPatientInformationKeys();
                    break;
                default:
                    $patient_information_keys = array_merge($patientInformation->initialPatientInformationKeys(), $patientInformation->updatedPatientInformationKeys());
            }
            DB::beginTransaction();
            $patient_information_data = [];
            foreach ($patient_information_keys as $key) {
                $patient_information_data[$key] = $request->patient_informations[$key];
                if ($key === 'code_status') {
                    if (!in_array($request->patient_informations['code_status'], $patientInformation->validCodeStatuses())) {
                        $response['message'] = "{$request->patient_informations['code_status']} is not a valid code status.";
                        return $response;
                    }
                } else if ($key === 'weight_units') {
                    if (!in_array($request->patient_informations['weight_units'], $patientInformation->validWeightUnits())) {
                        $response['message'] = "{$request->patient_informations['weight_units']} is not a valid code status.";
                        return $response;
                    }
                } else if ($request->type === 'initial conditions' && !$request->patient_informations[$key]) {
                    $response['message'] = "You are missing $key for the Initial Patient Information.";
                    return $response;
                }
            }
            PatientInformation::updateOrCreate(['assignment_id' => $assignment->id], $patient_information_data);

            switch ($request->type) {
                case('initial conditions'):
                    $caseStudyNote->updateBasedOnVersion($request, 0);
                    $message = "The Initial Conditions have been saved.";
                    break;
                case('updated information'):
                    $caseStudyNote->updateBasedOnVersion($request, 1);
                    $message = "The Updated Information has been saved.";
                    break;
                case('all'):
                    $caseStudyNote->updateBasedOnVersion($request, 0);
                    $caseStudyNote->updateBasedOnVersion($request, 1);
                    $assignment->common_question_text = $request->common_question_text;
                    $assignment->save();
                    $message = "All of your Case Study Notes have been saved.";
                    break;
                default:
                    throw new Exception ("$request->type is not a valid case study notes type.");
            }
            $response['type'] = 'success';
            $response['message'] = $message;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving all of the case study notes. Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Assignment $assignment
     * @param CaseStudyNote $caseStudyNote
     * @return array
     * @throws Exception
     */
    public
    function show(Assignment $assignment, CaseStudyNote $caseStudyNote): array
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

    public
    function destroy(CaseStudyNote $caseStudyNote): array
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
