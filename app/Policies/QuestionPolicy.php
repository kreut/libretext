<?php

namespace App\Policies;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Course;
use App\Helpers\Helper;
use App\LearningTree;
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
     * @return Response
     */
    public function getQtiAnswerJson(User $user): Response
    {
        return in_array($user->role, [2, 5])
            ? Response::allow()
            : Response::deny("You are not allowed to get the answer to this question.");

    }

    /**
     * @param User $user
     * @param Question $question
     * @return Response
     */
    public function clone(User $user, Question $question): Response
    {
        $has_access = true;
        $message = '';
        if ($user->id !== $question->question_editor_user_id) {
            if (in_array($question->license, ['ccbyncnd', 'ccbynd', 'arr'])) {
                $message = "Due to licensing restrictions, this question cannot be cloned.";
                $has_access = false;
            }

            if (!$question->public) {
                $message = "This is a private question and cannot be cloned.";
                $has_access = false;
            }
            if (!in_array($user->role, [2, 5])) {
                $message = "You are not allowed to clone questions.";
                $has_access = false;
            }
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);

    }

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
        if (!in_array($user->role, [2, 5])) {
            $message = "You are not allowed to refresh questions.";
            $has_access = false;
        } else if (!$user->isMe()
            && $assignmentSyncQuestion->questionExistsInOtherAssignments($assignment, $question)
            && $assignmentSyncQuestion->questionHasAutoGradedOrFileSubmissionsInOtherAssignments($assignment, $question)) {
            $has_access = false;
            $message = "You cannot refresh this question since there are already submissions in other assignments.";

        } else if (!$user->isMe() && $assignment->isBetaAssignment()) {
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
    public function getWebworkCodeFromFilePath(User $user): Response
    {
        return in_array($user->role, [2, 5])

            ? Response::allow()
            : Response::deny("You are not allowed to get the weBWork code.");
    }

    /**
     * @param User $user
     * @return Response
     */
    public function exportWebworkCode(User $user): Response
    {
        return in_array($user->role, [2, 5])

            ? Response::allow()
            : Response::deny("You are not allowed to export the weBWork code.");
    }


    /**
     * @param User $user
     * @return Response
     */
    public function getQuestionForEditing(User $user)
    {
        return in_array($user->role, [2, 5])

            ? Response::allow()
            : Response::deny("You are not allowed to get that question for editing.");
    }

    /**
     * @param User $user
     * @param Question $question
     * @param $assignment_id
     * @return Response
     */
    public function storeH5P(User $user, Question $question, $assignment_id): Response
    {
        $allow = true;
        $message = '';
        if (!in_array($user->role, [2, 5])) {
            $allow = false;
            $message = "You are not allowed to bulk upload H5P questions.";
        }

        if ($assignment_id) {
            $owns_assignment = DB::table('assignments')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->where('courses.user_id', $user->id)
                ->where('assignments.id', $assignment_id)
                ->first();
            if (!$owns_assignment) {
                $allow = false;
                $message = "You do not own that assignment.";
            }

        }

        return $allow
            ? Response::allow()
            : Response::deny($message);
    }

    public function index(User $user): Response
    {
        return (in_array($user->role, [2, 5]))
            ? Response::allow()
            : Response::deny("You are not allowed to view My Questions.");
    }

    public function destroy(User $user, Question $question): Response
    {

        return ((int)$question->question_editor_user_id === (int)$user->id) || $user->isMe()
            ? Response::allow()
            : Response::deny("You are not allowed to delete that question.");
    }

    public function store(User $user, Question $question, int $folder_id): Response
    {
        $authorize = true;
        $message = "no message provided";
        if (!in_array($user->role, [2, 5])) {
            $authorize = false;
            $message = "You are not allowed to save questions.";
        }
        if ($authorize) {
            if (!$this->_ownsFolder($folder_id)) {
                $authorize = false;
                $message = "That is not your My Questions folder.";
            }
        }
        return $authorize
            ? Response::allow()
            : Response::deny($message);
    }

    private function _ownsFolder($folder_id)
    {
        return DB::table('saved_questions_folders', $folder_id)
            ->where('user_id', auth()->user()->id)
            ->where('type', 'my_questions')
            ->first();
    }

    public function update(User $user, Question $question, $folder_id): Response
    {

        $message = 'Unknown authorization user to update question';
        if ($user->isAdminWithCookie()) {
            $authorize = true;
        } else if ($user->role === 5) {
            $authorize = true;
            $question_editor = User::find($question->question_editor_user_id);
            if ($question_editor->role !== 5) {
                $authorize = false;
                $message = "You are a non-instructor editor but the question was created by someone who is not a non-instructor editor.";
            }
        } else {
            $authorize = $user->isDeveloper() || $user->isMe() || ((int)$user->id == $question->question_editor_user_id
                    //&& !$question->questionExistsInAnotherInstructorsAssignments()
                    && ($user->role === 2)
                    && $this->_ownsFolder($folder_id));
            if (!$authorize) {
                if ((int)$user->id !== $question->question_editor_user_id) {
                    $user = User::find($question->question_editor_user_id);
                    $message = "This is not your question to edit. This question is owned by $user->first_name $user->last_name.";
                } else if ($question->questionExistsInAnotherInstructorsAssignments()) {
                    // $message = "You cannot edit this question since it is in another instructor's assignment.";
                } else if ($user->role !== 2) {
                    $message = "You are not allowed to edit this newly created question.";
                } else {
                    $message = "That is not one of your My Questions folders.";
                }
            }
        }
        return $authorize
            ? Response::allow()
            : Response::deny($message);
    }

    public function validateBulkImport(User $user, Question $question, $course_id): Response
    {
        $has_access = true;
        $message = '';
        if (!in_array($user->role, [2, 5])) {
            $has_access = false;
            $message = "You are not allowed to bulk import questions.";
        }
        if ($has_access
            && $course_id
            && Course::find($course_id)->user_id !== $user->id) {
            $has_access = false;
            $message = "You are not allowed to bulk import questions into a course that you don't own.";
        }
        return ($has_access)
            ? Response::allow()
            : Response::deny($message);
    }


    public function getQuestionTypes(User $user): Response
    {
        return in_array($user->role, [2, 4, 5])
            ? Response::allow()
            : Response::deny("You are not allowed to get the question types.");

    }

    public function updateProperties(User $user): Response
    {
        return ($user->role === 2)
            ? Response::allow()
            : Response::deny("You are not allowed to update the question's properties.");

    }

    /**
     * @param User $user
     * @param Question $question
     * @param Assignment $assignment
     * @param LearningTree $learningTree
     * @param int $active_id
     * @param int $question_id
     * @return Response
     */
    public function getRemediationByQuestionIdInLearningTreeAssignment(User         $user,
                                                                       Question     $question,
                                                                       Assignment   $assignment,
                                                                       LearningTree $learningTree,
                                                                       int          $active_id,
                                                                       int          $question_id): Response
    {

        $question_in_assignment = DB::table('assignment_question')
            ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
            ->join('enrollments', 'assignments.course_id', '=', 'enrollments.course_id')
            ->where('enrollments.user_id', $user->id)
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->first();

        $remediation_question_id = 0;
        $blocks = json_decode($learningTree->learning_tree)->blocks;
        foreach ($blocks as $block) {
            if ((int)$block->id === $active_id) {
                foreach ($block->data as $info) {
                    if ($info->name === 'question_id') {
                        $remediation_question_id = (int)$info->value;
                    }
                }
            }
        }

        $has_access = $question_id === $remediation_question_id
            && $question_in_assignment
            && $user->role === 3;
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to view that remediation.');

    }

    public function viewAny(User $user)
    {

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

    /**
     * @param User $user
     * @param Question $question
     * @param $question_id
     * @return bool|Response
     */
    public function getHeaderHtml(User $user, Question $question, $question_id)
    {
        //set when viewing remediations
        if (session()->get('canViewLocallySavedContents')) {
            return true;
        }

        switch ($user->role) {
            case(2):
            case(4):
            case(5):
                $has_access = true;
                break;
            case(3):
                $Question = $question->where('id', $question_id)->first();
                $has_access = $Question !== null ? $Question->canBeViewedByStudent($user) : false;
                break;
            default:
                $has_access = false;
        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to view the text associated with this question.');

    }


}

