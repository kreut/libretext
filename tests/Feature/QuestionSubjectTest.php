<?php

namespace Tests\Feature;

use App\QuestionSubject;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionSubjectTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create(['role' => 2]);
        $this->user_2 = factory(User::class)->create(['role' => 3]);
    }

    /** @test */
    public function non_instructor_cannot_store_a_subject()
    {
        $this->actingAs($this->user_2)
            ->postJson("/api/question-subjects", ['name' => 'some name'])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to add subjects.']);
    }
    /** @test */
    public function subject_names_must_be_unique()
    {
        DB::table('question_subjects')->insert(['name' => 'some name']);
        $this->actingAs($this->user)
            ->postJson("/api/question-subjects", ['name' => 'some name'])
            ->assertJsonValidationErrors('name');
    }

    /** @test */
    public function non_instructor_cannot_update_a_subject()
    {
        $question_subject_id = DB::table('question_subjects')->insertGetId(['name' => 'some name']);
        $this->actingAs($this->user_2)
            ->patchJson("/api/question-subjects/$question_subject_id", ['name' => 'some name'])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to update subjects.']);
    }
}
