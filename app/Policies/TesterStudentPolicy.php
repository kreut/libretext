<?php

namespace App\Policies;

use App\TesterStudent;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class TesterStudentPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param TesterStudent $testerStudent
     * @param User $student
     * @return Response
     */
    public function emailResults(User $user, TesterStudent $testerStudent, User $student): Response
    {
        $tester_student_of_user = DB::table('tester_students')
            ->where('tester_user_id', $user->id)
            ->where('student_user_id', $student->id)
            ->first();
        return $tester_student_of_user
            ? Response::allow()
            : Response::deny("You are not allowed to email these student results.");


    }
}
