<?php

namespace App\Policies;

use App\QuestionChapter;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use \App\Traits\CommonPolicies;

class QuestionChapterPolicy
{
    use HandlesAuthorization;
    use CommonPolicies;

    public function store(User $user, QuestionChapter $questionChapter): Response
    {
        return $this->storeQuestionSubjectChapterSection($user, 'chapter');
    }

    public function update(User $user, QuestionChapter $questionChapter): Response
    {
        return $this->updateQuestionSubjectChapterSection($user, 'chapter');
    }
}
