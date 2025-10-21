<?php

namespace App\Policies;

use App\QuestionSubject;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use \App\Traits\CommonPolicies;

class QuestionSubjectPolicy
{
    use HandlesAuthorization;
    use CommonPolicies;

    public function store(User $user, QuestionSubject $questionSubject): Response
    {
        return $this->storeQuestionSubjectChapterSection($user, 'subject');
    }

    public function update(User $user, QuestionSubject $questionSubject): Response
    {
        return $this->updateQuestionSubjectChapterSection($user, 'subject');
    }
    public function destroy(User $user, QuestionSubject $questionSubject): Response
    {
        return $this->destroyQuestionSubjectChapterSection($user, 'subject');
    }
}
