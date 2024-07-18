<?php

namespace App\Policies;

use App\Assignment;
use App\Discussion;
use App\DiscussionComment;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class DiscussionCommentPolicy
{
    use HandlesAuthorization;

    public function destroy(User $user, DiscussionComment $discussionComment): Response
    {
        switch ($user->role) {
            case(3):
                $has_access = $discussionComment->user_id === $user->id;
                break;
            case(2):
                $discussion = Discussion::find($discussionComment->discussion_id);
                $assignment = Assignment::find($discussion->assignment_id);
                $has_access = $assignment->course->user_id === $user->id;
                break;
            default:
                $has_access = false;
        }

        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to delete this comment.');

    }
}
