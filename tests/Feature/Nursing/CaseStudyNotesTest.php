<?php

namespace Tests\Feature\Nursing;

use App\CaseStudyNote;
use App\User;
use App\Course;
use App\Assignment;
use Tests\TestCase;

class CaseStudyNotesTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->case_study_notes = new CaseStudyNote();
        $this->case_study_notes->assignment_id = $this->assignment->id;
        $this->case_study_notes->type = 'progress_notes';
        $this->case_study_notes->save();

    }

    /** @test */
    public function case_study_notes_cannot_do_save_all_by_non_owner()
    {
        $this->actingAs($this->user_2)->postJson("/api/case-study-notes/save-all", ['assignment_id' => $this->assignment->id])
            ->assertJson(['message' => 'You are not allowed to save all of the Case Study Notes.']);
    }

    /** @test */
    public function non_owner_cannot_get_unsaved_case_study_notes()
    {
        $this->actingAs($this->user_2)->postJson("/api/case-study-notes/unsaved-changes", ['assignment_id' => $this->assignment->id])
            ->assertJson(['message' => 'You are not allowed to get the unsaved changes.']);
    }


    /** @test */
    public function non_owner_cannot_get_case_study_notes()
    {
        $this->actingAs($this->user_2)->getJson("/api/assignments/{$this->assignment->id}/common-question-text")
            ->assertJson(['message' => 'You are not allowed to get the common question text for this assignment.']);
    }

    /** @test */
    public function non_owner_cannot_update_case_study_notes()
    {
        $this->actingAs($this->user_2)->patchJson("/api/assignments/{$this->assignment->id}/common-question-text", ['common_question_text' => 'some other text'])
            ->assertJson(['message' => 'You are not allowed to update the common question text for this assignment.']);

    }

    /** @test */
    public function owner_can_update_case_study_notes()
    {
        $this->actingAs($this->user)->patchJson("/api/assignments/{$this->assignment->id}/common-question-text", ['common_question_text' => 'some other text'])
            ->assertJson(['type' => 'success']);
        $this->assertEquals(Assignment::find($this->assignment->id)->common_question_text, 'some other text');

    }


    /** @test */
    public function case_study_notes_cannot_be_reset_by_non_owner()
    {
        $this->actingAs($this->user_2)->deleteJson("/api/case-study-notes/assignment/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to reset these Case Study Notes.']);
    }

    /** @test */
    public function case_study_notes_can_be_reset_by_owner()
    {
        $this->actingAs($this->user)->deleteJson("/api/case-study-notes/assignment/{$this->assignment->id}")
            ->assertJson(['type' => 'info']);
        $this->assertDatabaseMissing('case_study_notes', ['id' => $this->case_study_notes->id]);
    }

    /** @test */
    public function case_study_notes_cannot_be_deleted_by_non_owner()
    {
        $this->actingAs($this->user_2)->deleteJson("/api/case-study-notes/{$this->case_study_notes->id}")
            ->assertJson(['message' => 'You are not allowed to delete these Case Study Notes.']);
    }

    /** @test */
    public function case_study_notes_can_be_deleted_by_owner()
    {
        $this->actingAs($this->user)->deleteJson("/api/case-study-notes/{$this->case_study_notes->id}")
            ->assertJson(['type' => 'info']);
        $this->assertDatabaseMissing('case_study_notes', ['id' => $this->case_study_notes->id]);
    }


    /** @test */
    public function case_study_notes_can_be_retrieved_by_non_owner()
    {
        $this->actingAs($this->user_2)->getJson("/api/case-study-notes/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to retrieve these Case Study Notes.']);
    }


    /** @test */
    public function case_study_notes_cannot_be_retrieved_by_non_owner()
    {
        $this->actingAs($this->user_2)->getJson("/api/case-study-notes/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to retrieve these Case Study Notes.']);
    }

    /** @test */
    public function non_owner_cannot_reset_notes()
    {
        $this->actingAs($this->user_2)->deleteJson("/api/case-study-notes/assignment/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to reset these Case Study Notes.']);
    }

    /** @test */
    public function case_study_notes_type_must_be_valid()
    {
        $this->actingAs($this->user)->patchJson("/api/case-study-notes/{$this->assignment->id}", ['type' => 'bad type'])
            ->assertJsonValidationErrors('type');
    }


}
