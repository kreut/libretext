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
        $this->admin_user = factory(User::class)->create(['role' => 2, 'email' => 'me@me.com']);
        $this->user_2 = factory(User::class)->create(['role' => 3]);
        $this->question_subject_id = DB::table('question_subjects')->insertGetId(['name' => 'some name']);

        $this->questionChapter = new QuestionChapter();
        $this->questionChapter->question_subject_id = $this->question_subject_id;
        $this->questionChapter->name = 'some name';
        $this->questionChapter->save();
    }

    /** @test */
    public function non_admin_cannot_delete_a_section()
    {
        $question_section_id = DB::table('question_sections')->insertGetId([
            'name' => 'some name',
            'question_chapter_id' => $this->questionChapter->id
        ]);
        $this->actingAs($this->user)
            ->deleteJson("/api/question-sections/$question_section_id")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to delete sections.']);
    }

    /** @test */
    public function correctly_deletes_subjects_chapters_sections()
    {

        DB::table('question_sections')->insertGetId([
            'name' => 'some name',
            'question_chapter_id' => $this->questionChapter->id]);
        $question_subject_id_2 = DB::table('question_subjects')->insertGetId(['name' => 'some name']);
        $question_chapter_id_2 = DB::table('question_chapters')->insertGetId([
            'name' => 'some name',
            'question_subject_id' => $question_subject_id_2]);
        $question_section_id = DB::table('question_sections')->insertGetId([
            'name' => 'some name',
            'question_chapter_id' => $question_chapter_id_2]);

        $this->assertDatabaseCount('question_subjects', 2);
        $this->assertDatabaseCount('question_chapters', 2);
        $this->assertDatabaseCount('question_sections', 2);
        $this->actingAs($this->admin_user)
            ->deleteJson("/api/question-sections/$question_section_id")
            ->assertJson(['type' => 'info']);
        $this->assertDatabaseCount('question_subjects', 2);
        $this->assertDatabaseCount('question_chapters', 2);
        $this->assertDatabaseCount('question_sections', 1);
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
