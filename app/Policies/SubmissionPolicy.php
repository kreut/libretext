<?php

namespace App\Policies;

use App\Assignment;
use App\User;
use App\Submission;
use App\Question;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\Response;
use App\Traits\GeneralSubmissionPolicy;

class SubmissionPolicy
{
    use HandlesAuthorization;
    use GeneralSubmissionPolicy;

    /**
     * @param User $user
     * @param Submission $submission
     * @param $assignment
     * @param int $assignment_id
     * @param int $question_id
     * @return Response]
     */
    public function store(User $user, $submission, $assignment, int $assignment_id, int $question_id)
    {
        $response = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment_id, $question_id);
        $has_access = $response['type'] === 'success';
        return $has_access
            ? Response::allow()
            : Response::deny($response['message']);
    }

    public function submissionPieChartData(User $user, Submission $submission, Assignment $assignment, Question $question)
    {

        $question_in_assignment = DB::table('assignment_question')
                                    ->where('assignment_id',$assignment->id)
                                    ->where('question_id', $question->id)
                                    ->first();

        switch ($user->role) {
            case(2):
                $has_access = $question_in_assignment && ($assignment->course->user_id === (int)$user->id);
                break;
            case(3):
                $has_access = $question_in_assignment && $assignment->course->enrollments->contains('user_id', $user->id);
                break;
            case(4):
                $has_access = $question_in_assignment && $assignment->course->isGrader();
                break;
            default:
                $has_access = false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to view this summary.');
    }


}
