<?php

namespace App\Policies;

use App\LearningTreeNodeSubmission;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LearningTreeNodeSubmissionPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param LearningTreeNodeSubmission $learningTreeNodeSubmission
     * @return Response
     */
   public function show(User $user, LearningTreeNodeSubmission $learningTreeNodeSubmission): Response
   {
       return $user->id ===  $learningTreeNodeSubmission->user_id
           ? Response::allow()
           : Response::deny('You are not allowed to show this learning tree node submission.');


   }
}
