<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentLevelOverride;
use App\CompiledPDFOverride;
use App\Exceptions\Handler;
use App\Question;
use App\QuestionLevelOverride;
use App\Submission;
use App\SubmissionOverride;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SubmissionOverrideController extends Controller
{
    /**
     * @param Assignment $assignment
     * @param SubmissionOverride $submissionOverride
     * @return array
     * @throws Exception
     */
    public
    function index(Assignment $assignment, SubmissionOverride $submissionOverride): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('index', [$submissionOverride, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment_level_overrides = DB::table('assignment_level_overrides')
                ->join('users', 'assignment_level_overrides.user_id', '=', 'users.id')
                ->where('assignment_id', $assignment->id)
                ->where('fake_student', 0)
                ->select('user_id AS value',
                    DB::raw("CONCAT(users.first_name, ' ',users.last_name) AS text"))
                ->get();

            $all_pdf_overrides = DB::table('compiled_pdf_overrides')
                ->join('users', 'compiled_pdf_overrides.user_id', '=', 'users.id')
                ->where('assignment_id', $assignment->id)
                ->where('fake_student', 0)
                ->select('user_id AS value',
                    'set_page_only',
                    DB::raw("CONCAT(users.first_name, ' ',users.last_name) AS text"))
                ->get();
            $compiled_pdf_overrides = [];
            $set_page_overrides = [];

            $question_level_overrides_info = DB::table('question_level_overrides')
                ->join('users', 'question_level_overrides.user_id', '=', 'users.id')
                ->where('assignment_id', $assignment->id)
                ->where('fake_student', 0)
                ->select('user_id',
                    'question_id',
                    'auto_graded',
                    'open_ended',
                    DB::raw("CONCAT(users.first_name, ' ',users.last_name) AS name"))
                ->get();
            $question_ids = [];
            foreach ($question_level_overrides_info as $override) {
                $question_ids[] = $override->question_id;
            }
            $question_orders_info = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->whereIn('question_id', $question_ids)
                ->select('question_id', 'order')
                ->get();
            $question_orders = [];
            foreach ($question_orders_info as $question) {
                $question_orders[$question->question_id] = $question->order;

            }
            $question_level_overrides_by_question_id = [];
            foreach ($question_level_overrides_info as $override) {
                $submission_type = ($override->auto_graded && $override->open_ended) ? "auto-graded and open-ended portions"
                    : ($override->auto_graded ? "auto-graded portion" : "open-ended portion");
                $order = $question_orders[$override->question_id];
                $text = "$override->name can submit the $submission_type for question $order";
                if (!isset($question_level_overrides[$override->question_id])) {
                    $question_level_overrides[$override->question_id] = [];
                }
                $question_level_overrides_by_question_id[$override->question_id][] = [
                    'value' => $override->user_id,
                    'text' => $text,
                    'submission_type' => $submission_type,
                    'order' => $order,
                    'question_id' => $override->question_id
                ];
            }

            foreach ($all_pdf_overrides as $pdf_override) {
                $pdf_override->set_page_only
                    ? $set_page_overrides[] = $pdf_override
                    : $compiled_pdf_overrides[] = $pdf_override;
            }

            if (count($assignment_level_overrides) === $assignment->course->enrolledUsers()->count()) {
                $assignment_level_overrides = [['text' => 'Everybody', 'value' => -1]];
            }


            if (count($compiled_pdf_overrides) === $assignment->course->enrolledUsers()->count()) {
                $compiled_pdf_overrides = [['text' => 'Everybody', 'value' => -1]];
            }

            if (count($set_page_overrides) === $assignment->course->enrolledUsers()->count()) {
                $set_page_overrides = [['text' => 'Everybody', 'value' => -1]];
            }
            $question_level_overrides = [];
            foreach ($question_level_overrides_by_question_id as $question_id => $values) {
                if (count($values) === $assignment->course->enrolledUsers()->count()) {
                    $submission_type = $values[0]['submission_type'];
                    $order = $values[0]['order'];
                    $question_level_overrides[] = ['text' => "Everybody can submit the $submission_type for question $order",
                        'value' => -1,
                        'question_id' => $question_id];
                } else {
                    foreach ($values as $question_level_override) {
                        $question_level_overrides[] = $question_level_override;
                    }
                }
            }
            $response['assignment_level_overrides'] = $assignment_level_overrides;
            $response['compiled_pdf_overrides'] = $compiled_pdf_overrides;
            $response['set_page_overrides'] = $set_page_overrides;
            $response['question_level_overrides'] = $question_level_overrides;
            $response['type'] = 'success';
        } catch
        (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the suubmission overrides.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param AssignmentLevelOverride $assignmentLevelOverride
     * @param CompiledPDFOverride $compiledPDFOverride
     * @param QuestionLevelOverride $questionLevelOverride
     * @param SubmissionOverride $submissionOverride
     * @return array
     * @throws Exception
     */
    public
    function update(Request                 $request,
                    Assignment              $assignment,
                    AssignmentLevelOverride $assignmentLevelOverride,
                    CompiledPDFOverride     $compiledPDFOverride,
                    QuestionLevelOverride   $questionLevelOverride,
                    SubmissionOverride      $submissionOverride): array
    {

        $response['type'] = 'error';
        $type = $request->type;
        $authorized = ($type === 'question-level')
            ? Gate::inspect('updateQuestionLevel', [$submissionOverride, $assignment, $request->question_id])
            : Gate::inspect('updateAssignmentLevel', [$submissionOverride, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $student_id = $request->student['value'];

        if ($type === 'question-level') {
            $auto_graded = in_array('auto-graded', $request->selected_submission_types);
            $open_ended = in_array('open-ended', $request->selected_submission_types);
            $question_id = $request->question_id;

        }
        if ($student_id === -1) {
            $student_ids = $assignment->course->enrollments->pluck('user_id');
            $student_name = 'Everybody';
        } else {
            $student_user = User::find($student_id);
            $is_student = $student_user && $student_user->enrollments->contains('id', $assignment->course->id);
            if (!$student_user && $is_student) {
                $response['message'] = "This student is not enrolled in the course.";
                return $response;
            }
            $student_ids = [$student_id];
            $student_name = $request->student['text'];
        }
        try {
            DB::beginTransaction();
            foreach ($student_ids as $student_id) {
                switch ($type) {
                    case('assignment-level'):
                        $assignmentLevelOverride->firstOrCreate(
                            [
                                'user_id' => $student_id,
                                'assignment_id' => $assignment->id
                            ]);
                        $message = "$student_name can now submit anything in the assignment.";
                        break;
                    case('question-level'):
                        $questionLevelOverride->updateOrCreate(
                            ['user_id' => $student_id,
                                'assignment_id' => $assignment->id,
                                'question_id' => $question_id],
                            ['auto_graded' => $auto_graded,
                                'open_ended' => $open_ended]);
                        if ($auto_graded && $open_ended) {
                            $submission_message = "auto-graded and open-ended";
                        } else {
                            $submission_message = $auto_graded ? "auto-graded" : "open-ended";
                        }
                        $message = "$student_name can now submit $submission_message submissions for question $request->question_order.";
                        break;
                    case('compiled-pdf'):
                    case('set-page-only'):
                        $compiledPDFOverride->updateOrCreate(
                            ['user_id' => $student_id,
                                'assignment_id' => $assignment->id],
                            ['set_page_only' => $type === 'set-page-only']);
                        $extra_message = $type === 'compiled-pdf' ? ' upload a compiled-pdf and ' : ' ';
                        $message = "$student_name can now{$extra_message}set pages for each question.";
                }

            }
            DB::commit();

            $response['message'] = $message;
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the compiled-pdf overrides.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Assignment $assignment
     * @param $studentUser
     * @param string $type
     * @param Question|null $question
     * @return array
     * @throws Exception
     */
    public
    function destroy(Assignment $assignment,
                                $studentUser,
                     string     $type,
                     Question   $question = null): array
    {

        $response['type'] = 'error';
        $submissionOverride = new SubmissionOverride();
        $authorized = ($type === 'question-level')
            ? Gate::inspect('deleteQuestionLevel', [$submissionOverride, $assignment, $question->id])
            : Gate::inspect('deleteAssignmentLevel', [$submissionOverride, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $student_user = User::find($studentUser);
        $is_student = $student_user && $student_user->enrollments->contains('id', $assignment->course->id);
        $is_everybody = (int)$studentUser === -1;
        if (!$is_student && !$is_everybody) {
            $response['message'] = 'Not valid';
            // return $response;
        }
        $student_user_ids = $is_everybody
            ? $assignment->course->enrollments->pluck('user_id')
            : [$student_user->id];
        try {
            switch($type){
                case('question-level'):
                    $question_level_query = DB::table('question_level_overrides')
                        ->where('assignment_id', $assignment->id)
                        ->whereIn('user_id', $student_user_ids);
                    if ($question) {
                        $question_level_query = $question_level_query->where('question_id', $question->id);
                    }
                    $question_level_query->delete();
                    break;
                case('compiled-pdf'):
                case('set-page-only'):
                DB::table('compiled_pdf_overrides')
                    ->where('assignment_id', $assignment->id)
                    ->whereIn('user_id', $student_user_ids)
                    ->where('set_page_only', $type === 'set-page-only' ? 1 : 0)
                    ->delete();
                    break;
                case('assignment-level'):
                    DB::table('assignment_level_overrides')
                        ->where('assignment_id', $assignment->id)
                        ->whereIn('user_id', $student_user_ids)
                        ->delete();
                    break;
            }

            $message_name = $is_everybody ? 'The overrides have' : "$student_user->first_name $student_user->last_name has";
            $response['message'] = "$message_name been removed.";
            $response['type'] = 'info';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the overrides from the database.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
