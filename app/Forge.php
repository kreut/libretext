<?php

namespace App;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Traits\GeneralSubmissionPolicy;

class Forge extends Model
{
    use GeneralSubmissionPolicy;

    private $secret;

    public function __construct()
    {
        $this->secret = DB::table('key_secrets')
            ->where('key', 'forge')
            ->first()
            ->secret;
    }

    /**
     * @param array $data
     * @return Response
     */
    public function store(array $data): Response
    {
        /**$url = config('services.antecedent.url') . '/api/adapt/assignment';
         * $jsonData = json_encode($data);
         *
         * $curl = "curl -X POST '{$url}' \\\n"
         * . "  -H 'Content-Type: application/json' \\\n"
         * . "  -H 'Authorization: Bearer {$this->secret}' \\\n"
         * . "  -d '{$jsonData}'";
         *
         * dd($curl);**/


        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $this->secret",
        ])->post(config('services.antecedent.url') . '/api/adapt/assignment', $data);
    }


    /**
     * @param AssignmentSyncQuestion $assignment_question
     * @param int $user_id
     * @return array|false
     */
    public function getAssignToTimingsByAssignmentAndQuestion(AssignmentSyncQuestion $assignment_question,
                                                              int                    $user_id)
    {
        $forge_settings = json_decode($assignment_question->forge_settings, 1);
        $drafts = $forge_settings['drafts'];
        $assign_tos = [];
        foreach ($drafts as $draft) {
            $assign_to_index = $this->getAssignToIndexByDraft($draft['assign_tos'], $user_id);
            if ($assign_to_index === -1) {
                return false;
            }
            $assign_to['available_from'] = $draft['assign_tos'][$assign_to_index]['available_from'];
            $assign_to['due'] = $draft['assign_tos'][$assign_to_index]['due'];
            $assign_tos[] = $assign_to;
        }
        return $assign_tos;
    }


    /**
     * @param AssignmentSyncQuestion $assignment_question
     * @param int $user_id
     * @param string $forge_draft_id
     * @return array|false
     */
    public function getAssignToTimingsByAssignmentAndQuestionAndDraftId(AssignmentSyncQuestion $assignment_question,
                                                                        int                    $user_id,
                                                                        string                 $forge_draft_id)
    {
        $forge_settings = json_decode($assignment_question->forge_settings, 1);
        $drafts = $forge_settings['drafts'];
        $assign_tos = [];
        foreach ($drafts as $draft) {
            if ($forge_draft_id === $draft['uuid']) {
                $assign_to_index = $this->getAssignToIndexByDraft($draft['assign_tos'], $user_id);
                if ($assign_to_index === -1) {
                    return false;
                }
                $assign_tos['available_from'] = $draft['assign_tos'][$assign_to_index]['available_from'];
                $assign_tos['due'] = $draft['assign_tos'][$assign_to_index]['due'];
                if (isset($draft['assign_tos'][$assign_to_index]['final_submission_deadline'])) {
                    $assign_tos['final_submission_deadline'] = $draft['assign_tos'][$assign_to_index]['final_submission_deadline'];
                }
            }
        }
        return $assign_tos;
    }

    public function getAssignToIndexByDraft(array $assign_tos, int $user_id)
    {
        foreach ($assign_tos as $index => $assign_to) {
            foreach ($assign_to['groups'] as $group) {
                $value = $group['value'];
                if (isset($value['user_id']) && +$value['user_id'] === $user_id) {
                    return $index;
                }
            }
        }
        foreach ($assign_tos as $index => $assign_to) {
            foreach ($assign_to['groups'] as $group) {
                $value = $group['value'];
                if (isset($value['section_id'])) {
                    if (DB::table('enrollments')->where('user_id', $user_id)
                        ->where('section_id', $value['section_id'])
                        ->exists()) {
                        return $index;
                    }
                }
            }
        }
        foreach ($assign_tos as $index => $assign_to) {
            foreach ($assign_to['groups'] as $group) {
                $value = $group['value'];
                if (isset($value['course_id'])) {
                    return $index;
                }
            }
        }
        return -1;
    }

    public function getAssignToDataForForge(
        array  $validation,
        string $forge_draft_id,
        string $central_identity_id,
        Forge  $forge
    ): array
    {
        $response = ['type' => 'error'];

        $user = $validation['user'];
        $assignment = $validation['assignment'];
        $assignment_question = $validation['assignment_question'];
        $parent_assignment_question = $validation['parent_assignment_question'];
        $forge_assignment_question = $validation['forge_assignment_question'];

        $assign_tos = $forge->getAssignToTimingsByAssignmentAndQuestionAndDraftId($parent_assignment_question, $user->id, $forge_draft_id);
        if (!$assign_tos) {
            $response['message'] = "We could not find the draft assign tos for draft with UUID $forge_draft_id and ADAPT user with UUID $central_identity_id.";
            return $response;
        }

        $can_submit = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment->id, $forge_assignment_question->adapt_question_id);

        if ($can_submit['type'] === 'success') {
            $now = Carbon::now();
            if (isset($assign_tos['available_from']) && $now->lt(Carbon::parse($assign_tos['available_from']))) {
                $can_submit['type'] = 'error';
                $can_submit['message'] = 'Cannot submit because this Forge question is not yet available.';
            }

            if (isset($assign_tos['due']) && $now->gt(Carbon::parse($assign_tos['due']))) {
                if (isset($assign_tos['final_submission_deadline'])) {
                    if ($now->gt(Carbon::parse($assign_tos['final_submission_deadline']))) {
                        $can_submit['type'] = 'error';
                        $can_submit['message'] = 'Cannot submit because this Forge question is past the final submission deadline.';
                    }
                } else {
                    $can_submit['type'] = 'error';
                    $can_submit['message'] = 'Cannot submit because this Forge question is past due.';
                }
            }
        }

        if ($can_submit['type'] === 'success') {
            if (SubmissionFile::where('assignment_id', $assignment_question->assignment_id)
                ->where('question_id', $assignment_question->question_id)
                ->where('user_id', $user->id)
                ->where('upload_count', '>=', 1)
                ->first()) {
                $can_submit['type'] = 'error';
                $can_submit['message'] = "Submission already exists. Cannot save Forge submission.";
            }
        }

        return [
            'type' => 'success',
            'can_submit' => $can_submit,
            'assign_tos' => $assign_tos,
            'assignment_question_id' => $assignment_question->id,
        ];
    }

    /**
     * Validates the user, assignment question, and enrollment for Forge operations.
     *
     * @param string $forge_draft_id
     * @param string $central_identity_id
     * @return array
     */

    public function validateForgeQuestionAccess(string $forge_draft_id, string $central_identity_id): array
    {
        $response = ['type' => 'error'];

        $user = User::where('central_identity_id', $central_identity_id)->first();
        if (!$user) {
            $fake_user = User::where('id', $central_identity_id)->where('fake_student', 1)->first();
            if (!$fake_user) {
                $response['message'] = "No user exists with UUID $central_identity_id.";
                return $response;
            } else {
                $user = $fake_user;
            }
        }

        $assignment_question_forge_draft = DB::table('assignment_question_forge_draft')
            ->where('forge_draft_id', $forge_draft_id)
            ->first();

        if (!$assignment_question_forge_draft) {
            $response['message'] = "No assignment question exists with forge draft ID $forge_draft_id.";
            return $response;
        }
        $assignment_question = AssignmentSyncQuestion::find($assignment_question_forge_draft->assignment_question_id);
        if (!$assignment_question) {
            $response['message'] = "No assignment question exists with that assignment question ID.";
            return $response;
        }

        $assignment = Assignment::find($assignment_question->assignment_id);
        $course = $assignment->course;
        $enrolled_user_ids = Enrollment::where('course_id', $course->id)->get('user_id')->pluck('user_id')->toArray();

        if (!in_array($user->id, $enrolled_user_ids)) {
            $response['message'] = "The student with UUID $user->central_identity_id is not enrolled in this course.";
            return $response;
        }

        $question = Question::find($assignment_question->question_id);
        $question_id = $question->forge_source_id ?: $question->id;

        $forge_assignment_question = DB::table('forge_assignment_questions')
            ->where('adapt_assignment_id', $assignment_question->assignment_id)
            ->where('adapt_question_id', $question_id)
            ->first();
        if (!$forge_assignment_question) {
            $response['message'] = "There is no Forge assignment question with that assignment ID and that question ID.";
            return $response;
        }


        $parent_assignment_question = AssignmentSyncQuestion::where('assignment_id', $assignment->id)
            ->where('question_id', $question_id)
            ->first();
        if (!$parent_assignment_question) {
            $response['message'] = "There is no parent ADAPT assignment with ADAPT assignment ID $forge_assignment_question->assignment_id and question $forge_assignment_question->question_id.";
            return $response;
        }


        $response['type'] = 'success';
        $response['user'] = $user;
        $response['assignment'] = $assignment;
        $response['assignment_question'] = $assignment_question;
        $response['parent_assignment_question'] = $parent_assignment_question;
        $response['forge_assignment_question'] = $forge_assignment_question;

        return $response;
    }

}
