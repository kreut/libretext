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

class AssignmentQuestionSyncCaseStudyNotesController extends Controller
{
    /**
     * @param Assignment $assignment
     * @param int $order
     * @param AssignmentQuestionCaseStudyNotes $assignmentQuestionCaseStudyNotes
     * @param CaseStudyNote $caseStudyNote
     * @return array
     * @throws Exception
     */
    public function index(Assignment                       $assignment,
                          int                              $order,
                          AssignmentQuestionCaseStudyNotes $assignmentQuestionCaseStudyNotes,
                          CaseStudyNote                    $caseStudyNote): array
    {
       /* $response['type'] = 'error';
        $question_id = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->first(); //just care that it's some question in the assignment
        $question = Question::find($question_id);
        $authorized = Gate::inspect('index', [$assignmentQuestionCaseStudyNotes, $assignment, $question]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
       */
        try {
            $patient_information = DB::table('patient_informations')
                ->where('assignment_id', $assignment->id)
                ->first();
            $assignment_case_study_notes = DB::table('case_study_notes')
                ->where('assignment_id', $assignment->id)
                ->where('version', 0)
                ->get();
            $assignment_case_study_notes_by_type = [];
            foreach ($assignment_case_study_notes as $value) {
                $assignment_case_study_notes_by_type[$value->type] = $value;
            }

            $updated_informations = DB::table('case_study_notes')
                ->where('assignment_id', $assignment->id)
                ->where('version', 1)
                ->get();
            $updated_informations_by_type = [];
            foreach ($updated_informations as $value) {
                $updated_informations_by_type[$value->type] = $value;
            }
            foreach ($assignment_case_study_notes_by_type as $key => $value) {
                if (isset($updated_informations_by_type[$value->type])
                    && $order >= $updated_informations_by_type[$value->type]->first_application) {
                    $assignment_case_study_notes_by_type[$key] = $updated_informations_by_type[$value->type];
                }
            }
            $case_study_notes = [];
            if ($patient_information) {
                if ($order >= $patient_information->first_application_of_updated_information) {
                    $patient_information->bmi = $patient_information->updated_bmi;
                    $patient_information->weight = $patient_information->updated_weight;
                }
                $case_study_notes[] = ['title' => 'Patient Information',
                    'text' => $patient_information];
            }
            foreach ($assignment_case_study_notes_by_type as $case_study_note) {


                $case_study_notes[] = [
                    'title' => $caseStudyNote->formatType($case_study_note->type),
                    'text' => $case_study_note->text];

            }
            $response['case_study_notes'] = $case_study_notes;
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
