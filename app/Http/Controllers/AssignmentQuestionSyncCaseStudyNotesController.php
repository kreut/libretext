<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentQuestionCaseStudyNotes;
use App\CaseStudyNote;
use App\Exceptions\Handler;
use App\Question;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AssignmentQuestionSyncCaseStudyNotesController extends Controller
{
    /**
     * @param Assignment $assignment
     * @param int $question_id
     * @param CaseStudyNote $caseStudyNote
     * @return array
     * @throws Exception
     */
    public function index(Assignment    $assignment,
                          int           $question_id,
                          CaseStudyNote $caseStudyNote): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $question = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question_id)
            ->first();
        //should always be able to find a question but have this in because of students who hadn't reloaded yet
        if ($question){
            $order = $question->order;
        } else {
            $response['type'] = 'success';
            $response['case_study_notes'] = [];
            return $response;
        }

        try {

            $patient_information = DB::table('patient_informations')
                ->where('assignment_id', $assignment->id)
                ->first();
            $case_study_notes_by_type = $caseStudyNote->getByType($assignment);
            $case_study_notes = [];

            if ($patient_information) {
                if ($patient_information->first_application_of_updated_information && $order >= $patient_information->first_application_of_updated_information) {
                    $patient_information->bmi = $patient_information->updated_bmi;
                    $patient_information->weight = $patient_information->updated_weight;
                }

                $case_study_notes[] = ['title' => 'Patient Information',
                    'text' => $patient_information,
                    'updated_information' => $order === $patient_information->first_application_of_updated_information];
            }
            foreach ($case_study_notes_by_type as $value) {

                $type = $value['type'];
                $case_study_notes[$type] = ['text' => null, 'title' => $caseStudyNote->formatType($type)];
                foreach ($value['notes'] as $notes) {
                    if ($order >= $notes['first_application'] && !$case_study_notes[$type]['text']) {
                        $case_study_notes[$type]['text'] = $notes->text;
                        $case_study_notes[$type]['updated_information'] = $order > 1 && $order === $notes['first_application'];
                    }
                }
            }
            $response['case_study_notes'] = array_values($case_study_notes);
            $response['type'] = 'success';
            return $response;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get the Case Study Notes.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param AssignmentQuestionCaseStudyNotes $assignmentQuestionCaseStudyNotes
     * @return array
     * @throws Exception
     */
    public function update(Request                          $request,
                           Assignment                       $assignment,
                           Question                         $question,
                           AssignmentQuestionCaseStudyNotes $assignmentQuestionCaseStudyNotes): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$assignmentQuestionCaseStudyNotes, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            DB::beginTransaction();

            DB::table('assignment_question_case_study_notes')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->delete();
            foreach ($request->selected as $selected) {
                $assignmentQuestionCaseStudyNotes = new AssignmentQuestionCaseStudyNotes();
                $assignmentQuestionCaseStudyNotes->assignment_id = $assignment->id;
                $assignmentQuestionCaseStudyNotes->question_id = $question->id;
                $assignmentQuestionCaseStudyNotes->case_study_notes_id = $selected;
                $assignmentQuestionCaseStudyNotes->save();
            }
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The Case Study notes have been updated.';
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the Case Study Notes for this question.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param $question_case_study_notes_by_id
     * @param $case_study_notes_id
     * @param $version_of_notes
     * @return bool
     */
    public function selected($question_case_study_notes_by_id, $case_study_notes_id, $version_of_notes): bool
    {
        return isset($question_case_study_notes_by_id[$case_study_notes_id])
            && $question_case_study_notes_by_id[$case_study_notes_id] === $version_of_notes;
    }
}
