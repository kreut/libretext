<?php

namespace App\Policies;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Helpers\Helper;
use App\User;
use App\Question;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;


class QuestionPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param Question $question
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param $assignment
     * @return bool|Response
     */
    public function refreshQuestion(User                   $user,
                                    Question               $question,
                                    AssignmentSyncQuestion $assignmentSyncQuestion,
                                                           $assignment)
    {
        if ($user->isAdminWithCookie()) {
            return true;
        }
        $has_access = true;
        $message = '';
        if ($user->role !== 2) {
            $message = "You are not allowed to refresh questions.";
            $has_access = false;
        } else if (!Helper::isAdmin()
            && $assignmentSyncQuestion->questionExistsInOtherAssignments($assignment, $question)
            && $assignmentSyncQuestion->questionHasAutoGradedOrFileSubmissionsInOtherAssignments($assignment, $question)) {
            $has_access = false;
            $message = "You cannot refresh this question since there are already submissions in other assignments.";

        } else if (!Helper::isAdmin() && $assignment->isBetaAssignment()) {
            $has_access = false;
            $message = "You cannot refresh this question since this is a Beta assignment. Please contact the Alpha instructor to request the refresh.";
        }

        return ($has_access)
            ? Response::allow()
            : Response::deny($message);

    }

    /**
     * @param User $user
     * @return Response
     */
    public function storeH5P(User $user): Response
    {
        return (in_array($user->role, [2, 5]))
            ? Response::allow()
            : Response::deny("You are not allowed to bulk upload H5P questions.");
    }

    public function index(User $user): Response
    {
        return (in_array($user->role, [2, 5]))
            ? Response::allow()
            : Response::deny("You are not allowed to view My Questions.");
    }

    public function destroy(User $user, Question $question): Response
    {

        return (int)$question->question_editor_user_id === (int)$user->id
            ? Response::allow()
            : Response::deny("You are not allowed to delete that question.");
    }

    public function store(User $user): Response
    {

        return (in_array($user->role, [2, 5]))
            ? Response::allow()
            : Response::deny("You are not allowed to save questions.");
    }

    public function update(User $user, Question $question): Response
    {
        $authorize = false;
        $message = 'none';
        if ($user->isAdminWithCookie()){
            $authorize = true;
        } else if ((int)$user->id !== (int)$question->question_editor_user_id) {
            $message = "This is not your question to edit.";
        } else if ($question->questionExistsInAnotherInstructorsAssignments()){
            $authorize = false;
            $message = "You cannot edit this question since it is in another instructor's assignment.";
        }
        return  $authorize
            ? Response::allow()
            : Response::deny(   $message );
    }

    public function validateBulkImportQuestions(User $user): Response
    {
        return (in_array($user->role, [2, 5]))
            ? Response::allow()
            : Response::deny("You are not allowed to bulk import questions.");
    }


    public function updateProperties(User $user): Response
    {
        return ($user->role === 2)
            ? Response::allow()
            : Response::deny("You are not allowed to update the question's properties.");

    }

    public function viewAny(User $user)
    {
        return true;
        return ($user->role !== 3)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the questions from the database.');

    }

    public function refreshProperties(User $user)
    {
        return ($user->role === 2)
            ? Response::allow()
            : Response::deny('You are not allowed to refresh the question properties from the database.');

    }

    public function viewByPageId(User $user, Question $question, string $library, int $page_id)
    {
        switch ($user->role) {
            case(2):
            case(4):
                $has_access = true;
                break;
            case(3):
                $has_access = Helper::isAnonymousUser() || DB::table('assignment_question')
                        ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                        ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
                        ->join('enrollments', 'assignments.course_id', '=', 'enrollments.course_id')
                        ->where('enrollments.user_id', $user->id)
                        ->where('questions.page_id', $page_id)
                        ->where('questions.library', $library)
                        ->where('enrollments.user_id', $user->id)
                        ->select('questions.id')
                        ->get()
                        ->isNotEmpty();
                break;
            case(5):
                $owns_question = DB::table('questions')->where('library', $library)
                    ->where('page_id', $page_id)
                    ->where('question_editor_user_id', $user->id)
                    ->first();
                $has_access = $library === 'preview' ||  $owns_question;
                break;
            default:
                $has_access = false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to view this non-technology question.');

    }


}

