<?php

namespace Tests\Feature;

use App\QuestionSubject;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionChapterTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create(['role' => 2]);
        $this->user_2 = factory(User::class)->create(['role' => 3]);
        $this->questionSubject = new QuestionSubject();
        $this->questionSubject->name = 'some name';
        $this->questionSubject->save();
    }

    /** @test */
    public function chapter_names_must_be_unique()
    {
        DB::table('question_chapters')
            ->insert(['name' => 'some name', 'question_subject_id' => $this->questionSubject->id]);
        $this->actingAs($this->user)
            ->postJson("/api/question-chapters/question-subject/{$this->questionSubject->id}", ['name' => 'some name'])
            ->assertJsonValidationErrors('name');
    }


    /** @test */
    public function non_instructor_cannot_store_a_chapter()
    {
        $this->actingAs($this->user_2)
            ->postJson("/api/question-chapters/question-subject/{$this->questionSubject->id}", ['name' => 'some name'])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to add chapters.']);
    }

    /** @test */
    public function non_instructor_cannot_update_a_chapter()
    {
        $question_chapter_id = DB::table('question_chapters')
            ->insertGetId(['name' => 'some name', 'question_subject_id' => $this->questionSubject->id]);
        $this->actingAs($this->user_2)
            ->patchJson("/api/question-chapters/$question_chapter_id", ['name' => 'some name'])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to update chapters.']);
    }
}
