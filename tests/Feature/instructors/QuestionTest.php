<?php

namespace Tests\Feature\instructors;


use App\Question;
use App\User;
use Tests\TestCase;

class QuestionTest extends TestCase
{

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        //create a student and enroll in the class
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->question = factory(Question::class)->create();

    }

/** @test */
    public function a_student_cannot_view_the_question_view_page(){
        $this->actingAs($this->student_user)->getJson( "/api/questions/{$this->question->id}")
            ->assertJson(['type' => 'error',
                'message' => "You are not allowed to retrieve the questions from the database."]);

    }

    /** @test */
    public function a_non_student_can_view_the_question_view_page(){
        $this->actingAs($this->user)->getJson( "/api/questions/{$this->question->id}")
            ->assertJson(['type' => 'success']);

    }
}
