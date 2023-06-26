<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Course;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\NonUpdatedQuestionRevision;
use App\PendingQuestionRevision;
use App\Question;
use App\QuestionRevision;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class NonUpdatedQuestionRevisionController extends Controller
{
    /**
     * @param Request $request
     * @param Course $course
     * @param NonUpdatedQuestionRevision $nonUpdatedQuestionRevision
     * @param QuestionRevision $questionRevision
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param PendingQuestionRevision $pendingQuestionRevision
     * @return array
     * @throws Exception
     */
    public function updateToLatestQuestionRevisionsByCourse(Request                    $request,
                                                            Course                     $course,
                                                            NonUpdatedQuestionRevision $nonUpdatedQuestionRevision,
                                                            QuestionRevision           $questionRevision,
                                                            AssignmentSyncQuestion     $assignmentSyncQuestion,
                                                            PendingQuestionRevision    $pendingQuestionRevision): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateToLatestQuestionRevisionsByCourse', [$nonUpdatedQuestionRevision, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        if ($request->user()->isMe() && !$request->understand_student_submissions_removed) {
            $response['message'] = "You need to confirm that you understand that all student submissions will be removed.";
            return $response;
        }
        try {
            $non_updated_assignment_questions_by_course = $nonUpdatedQuestionRevision->nonUpdatedAssignmentQuestionsByCourse($course, $questionRevision);
            $assignment_ids = [];
            $question_ids = [];
            foreach ($non_updated_assignment_questions_by_course as $non_updated_assignment_question) {
                $assignment_id = $non_updated_assignment_question->assignment_id;
                $question_id = $non_updated_assignment_question->question_id;
                $assignment_ids[] = $assignment_id;
                $question_ids[] = $question_id;
            }
            $assignment_ids = array_unique($assignment_ids);
            $question_ids = array_unique($question_ids);
            $questions = Question::whereIn('id', $question_ids)->get();
            $questions_by_question_id = [];
            foreach ($questions as $question) {
                $questions_by_question_id[$question->id] = $question;
            }
            $assignments_by_assignment_id = [];
            $assignments = Assignment::whereIn('id', $assignment_ids)->get();
            foreach ($assignments as $assignment) {
                $assignments_by_assignment_id[$assignment->id] = $assignment;
            }

            DB::beginTransaction();
            $removed_student_submissions = false;
            foreach ($non_updated_assignment_questions_by_course as $non_updated_assignment_question) {
                $assignment_id = $non_updated_assignment_question->assignment_id;
                $question_id = $non_updated_assignment_question->question_id;
                $assignment = $assignments_by_assignment_id[$assignment_id];
                $question = $questions_by_question_id[$question_id];
                $assignmentSyncQuestion->where('assignment_id', $assignment_id)
                    ->where('question_id', $question_id)
                    ->update(['question_revision_id' => $non_updated_assignment_question->latest_question_revision_id]);
                $pendingQuestionRevision->where('assignment_id', $assignment_id)->where('question_id', $question_id)->delete();
                $removed_student_submissions = $assignmentSyncQuestion->questionHasSomeTypeOfRealStudentSubmission($assignment, $question);
                $assignmentSyncQuestion->updateAssignmentScoreBasedOnRemovedQuestion($assignment, $question);
                Helper::removeAllStudentSubmissionTypesByAssignmentAndQuestion($assignment_id, $question_id);
            }
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The question has been updated to the latest revision.';
            $response['message'] .= $removed_student_submissions ?
                ' In addition, student submissions were removed and scores were updated.'
                : ' There were no student submissions which needed to be removed.';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to update the question revisions for this course.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Course $course
     * @param NonUpdatedQuestionRevision $nonUpdatedQuestionRevision
     * @param QuestionRevision $questionRevision
     * @return array
     * @throws Exception
     */
    public function getNonUpdatedAssignmentQuestionsByCourse(Course                     $course,
                                                             NonUpdatedQuestionRevision $nonUpdatedQuestionRevision,
                                                             QuestionRevision           $questionRevision): array
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('getNonUpdatedQuestionRevisionsByCourse', [$nonUpdatedQuestionRevision, $course]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['non_updated_question_revisions'] = $nonUpdatedQuestionRevision->nonUpdatedAssignmentQuestionsByCourse($course, $questionRevision);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get the non-updated assignment questions for this course.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
