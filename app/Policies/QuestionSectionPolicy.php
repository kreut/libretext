<?php

namespace App\Policies;

use App\QuestionSection;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use \App\Traits\CommonPolicies;

class QuestionSectionPolicy
{
    use HandlesAuthorization;
    use CommonPolicies;

    public function store(User $user, QuestionSection $questionSection): Response
    {
        return $this->storeQuestionSubjectChapterSection($user, 'section');
    }

    public function update(User $user, QuestionSection $questionSection): Response
    {
        return $this->updateQuestionSubjectChapterSection($user, 'section');
    }
}
