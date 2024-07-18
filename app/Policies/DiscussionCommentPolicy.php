<?php

namespace App\Policies;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Discussion;
use App\DiscussionComment;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;
use App\Traits\GeneralSubmissionPolicy;

class DiscussionCommentPolicy
{
    use HandlesAuthorization;
    use GeneralSubmissionPolicy;


    /**
     * @param $discussionComment
     * @param $user
     * @param $assignment_id
     * @param $question_id
     * @param $action
     * @return array
     */
    private function _hasAccess($discussionComment, $user, $assignment_id, $question_id, $action): array
    {
        $message = "You are not allowed to $action this comment.";
        switch ($user->role) {
            case(3):
                $has_access = true;
                $general_submission_policy = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, Assignment::find($assignment_id), $assignment_id, $question_id);
                if ($discussionComment->user_id !== $user->id) {
                    $has_access = false;
                } else if ($action && !(new AssignmentSyncQuestion())->discussItSetting($assignment_id, $question_id, "students_can_{$action}_comments")) {
                    $has_access = false;
                    $message = "Your instructor's settings indicate you may not $action your comments.";
                } else if (DB::table('submission_files')
                    ->where('assignment_id', $assignment_id)
                    ->where('question_id', $question_id)
                    ->where('user_id', $user->id)
                    ->whereNotNull('grader_id')
                    ->first()) {
                    $has_access = false;
                    $message = "You cannot $action this comment since it was already graded.";
                } else if ($general_submission_policy['type'] === 'error') {
                    $has_access = false;
                    $message = $general_submission_policy['message'];
                }
                break;
            case(2):
                $discussion = Discussion::find($discussionComment->discussion_id);
                $assignment = Assignment::find($discussion->assignment_id);
                $has_access = $assignment->course->user_id === $user->id;
                break;
            default:
                $has_access = false;
        }
        return compact('has_access', 'message');
    }

    /**
     * @param User $user
     * @param DiscussionComment $discussionComment
     * @return Response
     */
    public function updateCaption(User $user, DiscussionComment $discussionComment){
        $discussion = Discussion::find($discussionComment->discussion_id);
        $assignment = Assignment::find($discussion->assignment_id);
        $has_access = $assignment->course->user_id === $user->id;
        return $has_access
            ? Response::allow()
            : Response::deny("You are not allowed to update this transcript.");

    }
    /**
     * @param User $user
     * @param DiscussionComment $discussionComment
     * @param int $assignment_id
     * @param int $question_id
     * @return Response
     */
    public function storeAudioDiscussionComment(User $user, DiscussionComment $discussionComment, int $assignment_id, int $question_id): Response
    {
        $has_access = true;
        $message = "You may not store audio discussion comments.";
        $assignment = Assignment::find($assignment_id);
        switch ($user->role) {
            case(3):
                $general_submission_policy = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment_id, $question_id);
                if ($general_submission_policy['type'] === 'error') {
                    $has_access = false;
                    $message = $general_submission_policy['message'];
                }
                break;
            case(2):
                $has_access = $assignment->course->user_id === $user->id;
                break;
            default:
                $has_access = false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);


    }

    /**
     * @param User $user
     * @param DiscussionComment $discussionComment
     * @param int $assignment_id
     * @param int $question_id
     * @return Response
     */
    public function destroy(User $user, DiscussionComment $discussionComment, int $assignment_id, int $question_id): Response
    {
        $has_access_info = $this->_hasAccess($discussionComment, $user, $assignment_id, $question_id, 'delete');
        return $has_access_info['has_access']
            ? Response::allow()
            : Response::deny($has_access_info['message']);

    }

    /**
     * @param User $user
     * @param DiscussionComment $discussionComment
     * @param int $assignment_id
     * @param int $question_id
     * @return Response
     */
    public function deletingWillMakeRequirementsNotSatisfied(User $user, DiscussionComment $discussionComment, int $assignment_id, int $question_id): Response
    {
        $has_access_info = $this->_hasAccess($discussionComment, $user, $assignment_id, $question_id, '');
        return $has_access_info['has_access']
            ? Response::allow()
            : Response::deny("You are not allowed to check whether deleting this comment will make the requirements not satisfied.");

    }


    /**
     * @param User $user
     * @param DiscussionComment $discussionComment
     * @param int $assignment_id
     * @param int $question_id
     * @return Response
     */
    public function update(User $user, DiscussionComment $discussionComment, int $assignment_id, int $question_id): Response
    {
        $has_access_info = $this->_hasAccess($discussionComment, $user, $assignment_id, $question_id, 'edit');
        return $has_access_info['has_access']
            ? Response::allow()
            : Response::deny($has_access_info['message']);

    }

    /**
     * @param User $user
     * @param DiscussionComment $discussionComment
     * @param int $assignment_id
     * @param int $student_user_id
     * @return Response
     */
    public function getSatisfiedRequirements(User              $user,
                                             DiscussionComment $discussionComment,
                                             int               $assignment_id,
                                             int               $student_user_id): Response
    {
        $has_access = false;
        $assignment = Assignment::find($assignment_id);
        switch ($user->role) {
            case(3):
                $has_access = $user->id === $student_user_id;
                break;
            case(2):
                $has_access = $assignment->course->user_id === $user->id;
                break;
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to view whether the requirements have been satisfied.');
    }
}
