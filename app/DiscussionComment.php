<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiscussionComment extends Model
{

    /**
     * @param int $assignment_id
     * @param int $question_id
     * @param int $user_id
     * @param Discussion $discussion
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     */
    public function satisfiedRequirements(int $assignment_id,
                                          int $question_id,
                                          int $user_id,
                                          Discussion $discussion,
                                          AssignmentSyncQuestion $assignmentSyncQuestion): array
    {
        $discuss_it_settings = json_decode($assignmentSyncQuestion->discussItSettings($assignment_id, $question_id));
        $number_of_comments_that_satisfied_the_requirements = $this->numberOfCommentsThatSatisfiedTheRequirements($assignment_id, $question_id, $user_id);
        $number_of_discussion_threads_that_satisfied_the_requirements = $discussion->numberOfDiscussionsThatSatisfiedTheRequirements($assignment_id, $question_id, $user_id);

        $satisfied_min_number_of_comments_requirement = !$discuss_it_settings->min_number_of_comments
            && $number_of_comments_that_satisfied_the_requirements >= 1 || $discuss_it_settings->min_number_of_comments && $number_of_comments_that_satisfied_the_requirements >= $discuss_it_settings->min_number_of_comments;

        $satisfied_min_number_of_discussion_threads_requirement = !$discuss_it_settings->min_number_of_discussion_threads
            && $number_of_discussion_threads_that_satisfied_the_requirements >= 1 || $discuss_it_settings->min_number_of_discussion_threads && $number_of_discussion_threads_that_satisfied_the_requirements >= $discuss_it_settings->min_number_of_discussion_threads;


        $response['satisfied_min_number_of_comments_requirement'] = $satisfied_min_number_of_comments_requirement;
        $response['satisfied_min_number_of_discussion_threads_requirement'] = $satisfied_min_number_of_discussion_threads_requirement;
        $number_of_comments_plural = $number_of_comments_that_satisfied_the_requirements !== 1 ? 's' : '';
        $number_of_discussion_threads_plural = $number_of_discussion_threads_that_satisfied_the_requirements !== 1 ? 's' : '';
        $response['number_of_comments_submitted'] = $number_of_comments_that_satisfied_the_requirements;
        $response['number_of_discussion_threads_participated_in'] = $number_of_discussion_threads_that_satisfied_the_requirements;
        $response['number_of_comments_submitted_message'] = "You have submitted $number_of_comments_that_satisfied_the_requirements comment$number_of_comments_plural.";
        $response['number_of_discussion_threads_participated_in_message'] = "You have participated in $number_of_discussion_threads_that_satisfied_the_requirements discussion thread$number_of_discussion_threads_plural.";
        $response['min_number_of_comments_required'] = $discuss_it_settings->min_number_of_comments;
        $response['min_number_of_discussion_threads'] = $discuss_it_settings->min_number_of_discussion_threads;
        return $response;
    }
    /**
     * @param int $assignment_id
     * @param int $question_id
     * @param int $user_id
     * @return mixed
     */
    public function numberOfCommentsThatSatisfiedTheRequirements(int $assignment_id, int $question_id, int $user_id)
    {
        return $this->join('discussions', 'discussion_comments.discussion_id', '=', 'discussions.id')
            ->where('discussions.assignment_id', $assignment_id)
            ->where('discussions.question_id', $question_id)
            ->where('discussion_comments.user_id', $user_id)
            ->where('discussion_comments.satisfied_requirement', 1)
            ->count();
    }

    public function getDir(): string
    {
        return ('uploads/discuss-it/discussion-comments');
    }
}
