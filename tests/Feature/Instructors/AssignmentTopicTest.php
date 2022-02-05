<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\AssignmentTopic;
use App\Course;
use App\Question;
use App\Traits\Test;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;


class AssignmentTopicTest extends TestCase
{
    use Test;

    /**Still must test the stuff with the correct/completed and number**/
    /**
     * @var Collection|Model|mixed
     */
    private $student_user;
    /**
     * @var Collection|Model|mixed
     */
    private $assignment;
    /**
     * @var array
     */
    private $new_topic;
    /**
     * @var Collection|Model|mixed
     */
    private $user_2;
    /**
     * @var Collection|Model|mixed
     */
    private $question;

    /** Should test that only an instructor can create an assignment... */
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->topic = factory(AssignmentTopic::class)->create(['assignment_id' => $this->assignment->id]);
        $this->topic_2 = factory(AssignmentTopic::class)->create(['assignment_id' => $this->assignment->id]);
        $this->new_topic = ['name' => 'Best topic ever', 'assignment_id' => $this->assignment->id];
        $this->question = factory(Question::class)->create(['page_id' => 1]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);
    }

    /** @test */
    public function owner_cannot_move_deleted_topic_questions_to_topic_outside_of_the_current_assignment()
    {

        $assignment_2 = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $topic_2 = factory(AssignmentTopic::class)->create(['assignment_id' => $assignment_2->id]);
        DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->update(['assignment_topic_id' => $this->topic->id]);
        $this->actingAs($this->user)
            ->postJson("/api/assignment-topics/delete/{$this->topic->id}",
                ['move_to_topic_id' => $topic_2->id])
            ->assertJson(['message' => "You cannot move the topic's questions to a topic in a different assignment."]);

    }

    /** @test */
    public function owner_can_move_deleted_topic_questions_to_a_different_topic_in_the_current_assignment()
    {

        $topic_2 = factory(AssignmentTopic::class)
            ->create(['assignment_id' => $this->assignment->id,
                'name' => 'some other topic']);
        DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->update(['assignment_topic_id' => $this->topic->id]);
        $this->actingAs($this->user)
            ->postJson("/api/assignment-topics/delete/{$this->topic->id}",
                ['move_to_topic_id' => $topic_2->id])
            ->assertJson(['message' => "{$this->topic->name} has been deleted.  All questions from {$this->topic->name} have been moved to $topic_2->name."]);
        $this->assertDatabaseHas('assignment_question', ['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'assignment_topic_id' => $topic_2->id]);
    }

    /** @test */
    public function if_there_are_no_topics_left_questions_get_moved_to_the_null_topic()
    {
        DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->update(['assignment_topic_id' => $this->topic->id]);
        $this->actingAs($this->user)
            ->postJson("/api/assignment-topics/delete/{$this->topic->id}")
            ->assertJson(['message' => "{$this->topic->name} has been deleted.  All questions from some topic have been moved to the base assignment."]);
        $this->assertDatabaseHas('assignment_question', ['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'assignment_topic_id' => null]);
    }

    /** @test */
    public function non_owner_cannot_delete_the_topic()
    {
        $this->actingAs($this->user_2)
            ->postJson("/api/assignment-topics/delete/{$this->topic->id}",
                ['move_to_topic_id' => $this->topic->id])
            ->assertJson(['message' => "You cannot delete a topic that you do not own."]);

    }

    /** @test */
    public function owner_can_delete_the_topic()
    {

        $this->actingAs($this->user)
            ->postJson("/api/assignment-topics/delete/{$this->topic->id}",
                ['move_to_topic_id' => $this->topic->id])
            ->assertJson(['type' => "info"]);

    }


    /** @test */
    public function can_move_a_question_from_a_topic_in_one_assignment_to_a_topic_in_the_same_assignment()
    {

        $this->actingAs($this->user)
            ->patchJson("/api/assignment-topics/move/from-assignment/{$this->assignment->id}/to/topic/{$this->topic_2->id}",
                ['question_ids_to_move' => [$this->question->id]])
            ->assertJson(['message' => "The question has been moved to {$this->topic_2->name}."]);

    }


    /** @test */
    public function cannot_move_a_question_from_a_topic_in_one_assignment_to_a_topic_in_another_assignment()
    {
        $assignment_2 = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $topic_2 = factory(AssignmentTopic::class)->create(['assignment_id' => $assignment_2->id]);

        $this->actingAs($this->user)
            ->patchJson("/api/assignment-topics/move/from-assignment/{$this->assignment->id}/to/topic/$topic_2->id",
                ['question_ids_to_move' => [$this->question->id]])
            ->assertJson(['message' => "You are trying to move a question from a topic in one assignment to a different assignment."]);
    }

    /** @test */
    public function cannot_move_a_question_to_an_assignment_that_you_do_not_own()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/assignment-topics/move/from-assignment/{$this->assignment->id}/to/topic/{$this->topic_2->id}",
                ['question_ids_to_move' => [$this->question->id]])
            ->assertJson(['message' => "You cannot move the question into an assignment that you do not own."]);


    }


    /** @test */
    public function cannot_move_a_question_that_is_not_in_the_assignment()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/assignment-topics/move/from-assignment/{$this->assignment->id}/to/topic/{$this->topic_2->id}",
                ['question_ids_to_move' => [9999999]])
            ->assertJson(['message' => "You are trying to move questions that are not in this assignment."]);

    }


    /** @test */
    public function instructor_must_own_the_parent_assignment()
    {
        $this->user_2 = factory(User::class)->create();
        $this->course_2 = factory(Course::class)->create(['user_id' => $this->user_2->id]);
        $this->assignment_2 = factory(Assignment::class)->create(['course_id' => $this->course_2->id]);
        $this->new_topic['assignment_id'] = $this->assignment_2->id;
        $this->actingAs($this->user)
            ->postJson("/api/assignment-topics", $this->new_topic)
            ->assertJson(['message' => "You are not the assignment owner so you cannot create a topic for that assignment."]);


    }

    /** @test */
    public function owner_of_topic_can_update_the_topic()
    {
        $topic_to_update = ['topic_id' => $this->topic->id,
            'name' => 'even better name'];
        $this->actingAs($this->user)
            ->patchJson("/api/assignment-topics", $topic_to_update)
            ->assertJson(['message' => "The topic's name has been updated to {$topic_to_update['name']}."]);


    }


    /** @test */
    public function an_instructor_can_create_a_topic()
    {
        $this->actingAs($this->user)
            ->postJson("/api/assignment-topics", $this->new_topic)
            ->assertJson(['message' => "{$this->new_topic['name']} has been created."]);

    }

    /** @test */
    public function non_owner_of_topic_cannot_update_the_topic()
    {
        $topic_to_update = ['topic_id' => $this->topic->id,
            'name' => 'new name'];
        $this->actingAs($this->student_user)
            ->patchJson("/api/assignment-topics", $topic_to_update)
            ->assertJson(['message' => "You are not allowed to update that topic."]);


    }

    /** @test */
    public function assignment_topic_must_be_unique_within_an_assignment()
    {
        $this->new_topic['name'] = $this->topic->name;
        $this->actingAs($this->user)
            ->postJson("/api/assignment-topics", $this->new_topic)
            ->assertJson(['errors' => ['name' => ['You already have a topic with that name within the assignment.']]]);
    }


    /** @test */
    public function assignment_topic_must_not_be_empty()
    {
        $this->new_topic['name'] = '';
        $this->actingAs($this->user)
            ->postJson("/api/assignment-topics", $this->new_topic)
            ->assertJsonValidationErrors('name');

    }

    /** @test */
    public function a_non_instructor_cannot_create_a_topic()
    {
        $this->actingAs($this->student_user)
            ->postJson("/api/assignment-topics", $this->new_topic)
            ->assertJson(['message' => "You are not allowed to create assignment topics."]);
    }


}
