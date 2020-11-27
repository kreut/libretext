<?php

namespace App\Policies;

use App\User;
use App\Course;
use App\LetterGrade;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use \App\Traits\CommonPolicies;

class LetterGradePolicy
{
    use HandlesAuthorization;
    use CommonPolicies;

   public function roundScores(User $user, LetterGrade $letterGrade, Course $course){
       return $this->ownsCourseByUser($course, $user)
           ? Response::allow()
           : Response::deny('You are not allowed do choose how scores are rounded.');

   }

    public function updateLetterGrades(User $user, LetterGrade $letterGrade, Course $course){
        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed do choose how scores are rounded.');

    }

    public function getCourseLetterGrades(User $user, LetterGrade $letterGrade, Course $course){
        return $this->ownsCourseByUser($course, $user)
            ? Response::allow()
            : Response::deny('You are not allowed do choose how scores are rounded.');

    }
}
