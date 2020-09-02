<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Question;
use App\User;
use App\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class QuestionsGetTest extends TestCase
{

    /**Still must test the stuff with the correct/completed and number**/
    /** Should test that only an instructor can create an assignment... */
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->user_2->role = 3;
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->question = factory(Question::class)->create();


    }

    /** @test */

    public function if_page_id_is_included_there_should_be_no_other_tags()
    {
        $this->markTestIncomplete(
            'TODO'
        );


    }

    /**@test */
    public function returns_an_error_with_an_invalid_page_id()
    {

        $this->markTestIncomplete(
            'TODO'
        );


    }

    /**@test */
    public function returns_the_correct_question_given_a_query_page_id()
    {
        $this->markTestIncomplete(
            'TODO'
        );

    }

    /** @test */

    public function user_gets_message_if_there_are_no_questions_associated_with_a_tag()
    {
        $this->markTestIncomplete(
            'TODO'
        );


    }

    /** @test */

    public function user_gets_message_if_there_are_no_questions_associated_with_an_intersection_of_tags()
    {
        $this->markTestIncomplete(
            'TODO'
        );


    }


    /** @test */
    public function can_get_tags_if_not_student()
    {
        $this->actingAs($this->user)->getJson("/api/tags")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_get_tags_if_student()
    {
        $this->actingAs($this->user_2)->getJson("/api/tags")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to retrieve the tags from the database.']);

    }

    /** @test */
    public function can_add_a_question_to_an_assignment_if_you_are_the_owner()
    {
        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'success']);
    }


    /** @test */
    public function cannot_add_a_question_to_an_assignment_if_you_are_not_the_owner()
    {
        $this->actingAs($this->user_2)->postJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to add a question to this assignment.']);
    }

    /** @test */
    public function can_remove_a_question_to_an_assignment_if_you_are_the_owner()
    {
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function cannot_remove_a_question_to_an_assignment_if_you_are_not_the_owner()
    {

        $this->actingAs($this->user_2)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to remove this question from this assignment.']);

    }

    /** @test */
    public function can_get_questions_by_tags()
    {
        $tag = factory(Tag::class)->create(['tag' => 'some tag']);
        $this->question->tags()->attach($tag);
        $this->actingAs($this->user)->postJson("/api/questions/getQuestionsByTags", ['tags' => ['some tag']])
            ->assertJson(['type' => 'success']);


    }

    /** @test */
    public function can_get_assignment_question_ids_if_owner()
    {
        $this->assignment->questions()->attach($this->question);

        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/questions/ids")
            ->assertJson(['type' => 'success',
                'question_ids' => "[{$this->question->id}]"]);

    }

    /** @test */
    public function cannot_get_assignment_question_ids_if_not_owner()
    {
        $this->assignment->questions()->attach($this->question);
        $this->actingAs($this->user_2)->getJson("/api/assignments/{$this->assignment->id}/questions/ids")
            ->assertJson(['type' => 'error']);

    }

}
