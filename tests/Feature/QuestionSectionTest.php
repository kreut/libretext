<?php

namespace Tests\Feature;

use App\QuestionChapter;
use App\QuestionSubject;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionSectionTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create(['role' => 2]);
        $this->user_2 = factory(User::class)->create(['role' => 3]);
        $question_subject_id = DB::table('question_subjects')->insertGetId(['name'=>'some name']);

        $this->questionChapter = new QuestionChapter();
        $this->questionChapter->question_subject_id = $question_subject_id;
        $this->questionChapter->name = 'some name';
        $this->questionChapter->save();
    }

    /** @test */
    public function non_instructor_cannot_store_a_section()
    {
        $this->actingAs($this->user_2)
            ->postJson("/api/question-sections/question-chapter/{$this->questionChapter->id}", ['name' => 'some name'])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to add sections.']);
    }

    /** @test */
    public function section_names_must_be_unique()
    {
        DB::table('question_sections')
            ->insert(['name' => 'some name', 'question_chapter_id' => $this->questionChapter->id]);
        $this->actingAs($this->user)
            ->postJson("/api/question-sections/question-chapter/{$this->questionChapter->id}", ['name' => 'some name'])
            ->assertJsonValidationErrors('name');
    }

    /** @test */
    public function non_instructor_cannot_update_a_section()
    {
        $question_section_id = DB::table('question_sections')
            ->insertGetId(['name' => 'some name', 'question_chapter_id' => $this->questionChapter->id]);
        $this->actingAs($this->user_2)
            ->patchJson("/api/question-sections/$question_section_id", ['name' => 'some name'])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to update sections.']);
    }
}
