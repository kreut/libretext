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
use Illuminate\Support\Facades\Log;

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
                    $case_study_note['type'] = $caseStudyNote->formatType($case_study_note['type']);
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

    public function store(Request $request, CaseStudyNote $caseStudyNote, Assignment $assignment): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('show', [$caseStudyNote, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        if (!in_array($request->type, $caseStudyNote->validCaseStudyNotes())) {
            $response['message'] = "$request->type is not a valid type of case study notes.";
            return $response;
        }
        try {

            DB::beginTransaction();
            $notes = CaseStudyNote::create(
                ['assignment_id' => $assignment->id,
                    'type' => $request->type,
                    'text' => null,
                    'first_application' => null
                ]
            );
            DB::commit();
            $response['notes'] = $notes;
            $response['type'] = 'success';
            $response['message'] = "The notes have been created.";
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the case study notes. Please try again or contact us for assistance.";
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
            $request->validated();
            DB::beginTransaction();
            $new_notes = CaseStudyNote::where('id', $request->id)->update(['text', $request->text]);

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

            $patient_information_keys = array_merge($patientInformation->initialPatientInformationKeys(), $patientInformation->updatedPatientInformationKeys());

            DB::beginTransaction();
            $errors_by_type = [];
            foreach ($request->case_study_notes as $case_study_note) {
                $type = $case_study_note['type'];
                $formatted_type = $caseStudyNote->formatType($case_study_note['type']);
                $first_applications = [];
                foreach ($case_study_note['notes'] as $notes) {
                    $first_applications[$notes['type']] = [];
                }
                foreach ($case_study_note['notes'] as $notes) {
                    if (!isset($errors_by_type[$type])) {
                        $errors_by_type[$type] = [];
                    }
                    if (!$notes['first_application']) {
                        $errors_by_type[$type][] = "All First Applications must be set for $formatted_type.";
                    } else {
                        if (in_array($notes['first_application'], $first_applications[$type])) {
                            $errors_by_type[$type][] = "Each First Application should be chosen only once for $formatted_type.";
                        } else {
                            $first_applications[$type][] = $notes['first_application'];
                        }
                    }
                }
            }
            $patient_information_data = [];
            foreach ($patient_information_keys as $key) {
                $patient_information_data[$key] = $request->patient_informations[$key];
                $formatted_key = str_replace('_', ' ', $key);
                if (strpos($key, 'updated') === false) {
                    if ($key === 'code_status') {
                        if (!in_array($request->patient_informations['code_status'], $patientInformation->validCodeStatuses())) {
                            $errors_by_type['patient_information'][] = "Please choose one of the code statuses for the Patient Information.";
                        }
                    } else if ($key === 'weight_units') {
                        if (!in_array($request->patient_informations['weight_units'], $patientInformation->validWeightUnits())) {
                            $errors_by_type['patient_information'][] = "Please choose one of the units of weight for the Patient Information";
                        }
                    }
                } else {
                    if ($request->patient_informations['first_application_of_updated_information'] && !$request->patient_informations[$key]) {
                        //missing an updated
                        $errors_by_type['patient_information'][] = "You are missing $formatted_key for the Patient Information.";
                    }

                    if (!$request->patient_informations['first_application_of_updated_information'] && $request->patient_informations[$key]) {
                        $errors_by_type['patient_information'][] = "You set a question for Updated Information but did not set an $formatted_key for the Patient Information.";

                    }

                }
            }

            $at_least_one_error_by_type = false;
            foreach ($errors_by_type as $error_by_type) {
                if (count($error_by_type)) {
                    $at_least_one_error_by_type = true;
                }
            }
            if ($at_least_one_error_by_type) {
                foreach ($errors_by_type as $key => $value) {
                    $errors_by_type[$key] = array_unique($value);
                }
                $response['errors_by_type'] = $errors_by_type;
                $response['errors'] = [];
                foreach ($errors_by_type as $key => $errors) {
                    foreach ($errors as $error) {
                        $response['errors'][] = $error;
                    }
                }
                return $response;
            }

            PatientInformation::updateOrCreate(['assignment_id' => $assignment->id], $patient_information_data);

            foreach ($request->case_study_notes as $value) {
                foreach ($value['notes'] as $notes) {
                    CaseStudyNote::where('id', $notes['id'])->update(['text' => $notes['text'],
                        'first_application' => $notes['first_application']
                    ]);
                }
            }

            DB::commit();
            $message = "Your Case Study Notes have been saved.";
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

            $response['case_study_notes'] = $caseStudyNote->getByType($assignment);
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
            $caseStudyNote->delete();

            $response['message'] = "The $formatted_type notes have been removed from your Case Study Notes.";
            $response['type'] = 'info';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting your case study notes. Please try again or contact us for assistance.";
        }
        return $response;
    }


    public
    function destroyType(Assignment $assignment, string $type, CaseStudyNote $caseStudyNote): array
    {
        $response['type'] = 'error';
        /*  $authorized = Gate::inspect('destroy', $caseStudyNote);

          if (!$authorized->allowed()) {
              $response['message'] = $authorized->message();
              return $response;
          }*/
        $formatted_type = $caseStudyNote->formatType($type);
        try {
            $caseStudyNote->where('type', $type)->where('assignment_id', $assignment->id)->delete();

            $response['message'] = "All of the $formatted_type have been removed from your Case Study Notes.";
            $response['type'] = 'info';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting your case study notes. Please try again or contact us for assistance.";
        }
        return $response;
    }
}
