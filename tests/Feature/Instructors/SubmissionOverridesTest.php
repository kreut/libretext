<?php

namespace Tests\Feature\Instructors;


use App\Course;
use App\Enrollment;
use App\Question;
use App\Section;
use App\User;
use App\Assignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

use App\Traits\Test;

class SubmissionOverridesTest extends TestCase
{

    use Test;

    /**Still must test the stuff with the correct/completed and number**/
    /** Should test that only an instructor can create an assignment... */
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->user_2 = factory(User::class)->create();
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->assignment_2 = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->student_user->save();
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        Enrollment::create(['course_id' => $this->course->id,
            'section_id' => $this->section->id,
            'user_id' => $this->student_user->id]);
        $this->question = factory(Question::class)->create(['page_id' => 1]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 5,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);
    }


    /** @test */

    public
    function non_owner_cannot_delete_compiled_pdf_overrides()
    {

        $this->actingAs($this->user_2)
            ->deleteJson("/api/submission-overrides/{$this->assignment->id}/{$this->student_user->id}/set-page")
            ->assertJson(['message' => "You are not allowed to delete the overrides for this assignment."]);

    }

    /** @test */

    public
    function owner_can_delete_compiled_pdf_overrides()
    {

        $this->actingAs($this->user)
            ->deleteJson("/api/submission-overrides/{$this->assignment->id}/{$this->student_user->id}/question-level/{$this->question->id}")
            ->assertJson(['type' => "info"]);
    }


    /** @test */

    public
    function non_owner_cannot_view_submission_overrides()
    {

        $this->actingAs($this->user_2)
            ->getJson("/api/submission-overrides/{$this->assignment->id}")
            ->assertJson(['message' => "You are not allowed to view the overrides for this assignment."]);

    }


    /** @test */

    public
    function non_owner_cannot_delete_question_level_overrides()
    {

        $this->actingAs($this->user_2)
            ->deleteJson("/api/submission-overrides/{$this->assignment->id}/{$this->student_user->id}/question-level/{$this->question->id}")
            ->assertJson(['message' => "You are not allowed to delete that override."]);

    }

    /** @test */

    public
    function owner_can_delete_question_level_overrides()
    {

        $this->actingAs($this->user)
            ->deleteJson("/api/submission-overrides/{$this->assignment->id}/{$this->student_user->id}/question-level/{$this->question->id}")
            ->assertJson(['type' => "info"]);
    }


    /** @test */

    public
    function non_owner_cannot_update_compiled_overrides()
    {

        $this->actingAs($this->user_2)
            ->patchJson("/api/submission-overrides/{$this->assignment->id}")
            ->assertJson(['message' => "You are not allowed to update the overrides for this assignment."]);

    }

    /** @test */

    public
    function owner_can_update_compiled_overrides()
    {

        $this->actingAs($this->user)
            ->patchJson("/api/submission-overrides/{$this->assignment->id}", ['type' => 'set-page', 'student' => ['value' => -1]])
            ->assertJson(['message' => "Everybody can now set pages for each question."]);

    }

    /** @test */

    public
    function non_owner_cannot_update_question_level_overrides()
    {

        $this->actingAs($this->user)
            ->patchJson("/api/submission-overrides/{$this->assignment->id}",
                ['type' => 'question-level', 'question_id' => 90932, 'student' => ['value' => -1]])
            ->assertJson(['message' => "You are not allowed to update the overrides for that combination of assignments and questions."]);

    }

    /** @test */

    public
    function owner_can_update_question_level_overrides()
    {

        $this->actingAs($this->user)
            ->patchJson("/api/submission-overrides/{$this->assignment->id}",
                ['type' => 'question-level',
                    'question_id' => $this->question->id,
                    'student' => ['value' => -1],
                    'selected_submission_types' => ['auto-graded']])
            ->assertJson(['type' => "success"]);


    }




}
