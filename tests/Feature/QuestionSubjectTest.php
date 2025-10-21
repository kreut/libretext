<?php

namespace Tests\Feature;

use App\Question;
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
        $this->admin_user = factory(User::class)->create(['role' => 2, 'email' => 'me@me.com']);
        $this->user_2 = factory(User::class)->create(['role' => 3]);
    }


    /** @test */
    public function non_admin_cannot_delete_a_subject()
    {
        $question_subject_id = DB::table('question_subjects')->insertGetId(['name' => 'some name']);
        $this->actingAs($this->user)
            ->deleteJson("/api/question-subjects/$question_subject_id")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to delete subjects.']);
    }


    /** @test */
    public function correctly_deletes_subjects_chapters_sections()
    {
        $question_subject_id = DB::table('question_subjects')->insertGetId(['name' => 'some name']);
        $question_chapter_id = DB::table('question_chapters')->insertGetId([
            'name' => 'some name',
            'question_subject_id' => $question_subject_id]);
        $question_section_id = DB::table('question_sections')->insertGetId([
            'name' => 'some name',
            'question_chapter_id' => $question_chapter_id]);
        $question_subject_id_2 = DB::table('question_subjects')->insertGetId(['name' => 'some name']);
        $question_chapter_id_2 = DB::table('question_chapters')->insertGetId([
            'name' => 'some name',
            'question_subject_id' => $question_subject_id_2]);
        $question_section_id_2 = DB::table('question_sections')->insertGetId([
            'name' => 'some name',
            'question_chapter_id' => $question_chapter_id_2]);

        $question_1 = factory(Question::class)->create([
            'question_subject_id' => $question_subject_id,
            'question_chapter_id' => $question_chapter_id,
            'question_section_id' => $question_section_id]);
        $question_2 = factory(Question::class)->create([
            'question_subject_id' => $question_subject_id_2,
            'question_chapter_id' => $question_chapter_id_2,
            'question_section_id' => $question_section_id_2]);
        $this->assertDatabaseHas('questions', [
            'id' => $question_1->id,
            'question_subject_id' => $question_subject_id,
            'question_chapter_id' => $question_chapter_id,
            'question_section_id' => $question_section_id]);
        $this->assertDatabaseHas('questions', [
            'id' => $question_2->id,
            'question_subject_id' => $question_subject_id_2,
            'question_chapter_id' => $question_chapter_id_2,
            'question_section_id' => $question_section_id_2]);
        $this->assertDatabaseCount('question_subjects', 2);
        $this->assertDatabaseCount('question_chapters', 2);
        $this->assertDatabaseCount('question_sections', 2);
        $this->actingAs($this->admin_user)
            ->deleteJson("/api/question-subjects/$question_subject_id")
            ->assertJson(['type' => 'info']);
        $this->assertDatabaseCount('question_subjects', 1);
        $this->assertDatabaseCount('question_chapters', 1);
        $this->assertDatabaseCount('question_sections', 1);
        $this->assertDatabaseHas('questions', [
            'id' => $question_1->id,
            'question_subject_id' => null,
            'question_chapter_id' => null,
            'question_section_id' => null]);
        $this->assertDatabaseHas('questions', [
            'id' => $question_2->id,
            'question_subject_id' => $question_subject_id_2,
            'question_chapter_id' => $question_chapter_id_2,
            'question_section_id' => $question_section_id_2]);
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
