<?php

namespace App\Policies;

use App\Assignment;
use App\AssignmentTopic;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class AssignmentTopicPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param AssignmentTopic $assignmentTopic
     * @return Response
     */
    public function delete(User $user, AssignmentTopic $assignmentTopic): Response
    {

        return $this->_isTopicOwner($user, $assignmentTopic->id)
            ? Response::allow()
            : Response::deny("You cannot delete a topic that you do not own.");
    }

    public function store(User $user, AssignmentTopic $assignmentTopic, Assignment $assignment)
    {
        $has_access = true;
        $message = '';
        if ($user->role !== 2) {
            $has_access = false;
            $message = 'You are not allowed to create assignment topics.';
        }
        if ($has_access && $assignment->course->user_id !== $user->id) {
            $has_access = false;
            $message = 'You are not the assignment owner so you cannot create a topic for that assignment.';
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);

    }

    /**
     * @param User $user
     * @param AssignmentTopic $assignmentTopic
     * @param int $assignment_topic_id
     * @return Response
     */
    public function update(User $user, AssignmentTopic $assignmentTopic, int $assignment_topic_id): Response
    {
        return $this->_isTopicOwner($user, $assignment_topic_id)
            ? Response::allow()
            : Response::deny("You are not allowed to update that topic.");


    }

    private function _isTopicOwner($user, $assignment_topic_id)
    {

        return DB::table('assignment_topics')
            ->join('assignments', 'assignment_topics.assignment_id', '=', 'assignments.id')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->where('user_id', $user->id)
            ->where('assignment_topics.id', $assignment_topic_id)
            ->first();


    }

    /**
     * @param User $user
     * @param AssignmentTopic $assignmentTopic
     * @param int $assignment_id
     * @param array $question_ids_to_move
     * @param int $assignment_topic_id
     * @return Response
     */
    public function move(User            $user,
                         AssignmentTopic $assignmentTopic,
                         int             $assignment_id,
                         array           $question_ids_to_move,
                         int             $assignment_topic_id): Response
    {

        $assignment_questions = DB::table('assignment_question')
            ->where('assignment_id', $assignment_id)
            ->select('question_id')
            ->get();
        $assignment_question_ids = $assignment_questions->isNotEmpty()
            ? $assignment_questions->pluck('question_id')->toArray()
            : [];

        $message = '';
        $has_access = DB::table('assignments')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->where('assignments.id', $assignment_id)
                ->where('user_id', $user->id)
                ->first() !== null;
        if (!$has_access) {
            $message = "You cannot move the question into an assignment that you do not own.";
        }
        if ($has_access && !$assignmentTopic->where('id', $assignment_topic_id)
                ->where('assignment_id', $assignment_id)
                ->exists()
        ) {
            $has_access = false;
            $message = "You are trying to move a question from a topic in one assignment to a different assignment.";
        }
        if ($has_access) {
            foreach ($question_ids_to_move as $question_id_to_move) {
                if ($has_access && !in_array($question_id_to_move, $assignment_question_ids)) {
                    $has_access = false;
                    $message = "You are trying to move questions that are not in this assignment.";
                }
            }
        }

        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

    /**
     * @param User $user
     * @param AssignmentTopic $assignmentTopic
     * @return Response
     */
    public function getAssignmentTopicsByCourse(User $user, AssignmentTopic $assignmentTopic){
        return $user->role === 2
            ? Response::allow()
            : Response::deny("You are not allowed to access the tlopics by course.");

    }

    /**
     * @param User $user
     * @param AssignmentTopic $assignmentTopic
     * @return Response
     */
    public function getAssignmentTopicsByAssignment(User $user, AssignmentTopic $assignmentTopic): Response
    {
        return $user->role === 2
            ? Response::allow()
            : Response::deny("You are not allowed to access the topics by assignment.");

    }

}
