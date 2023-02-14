<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Grader;
use App\Section;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContactGraderOverrideTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        $this->user_2 = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->student_user_2 = factory(User::class)->create(['role' => 3]);
        $this->grader_user = factory(User::class)->create(['role' => 4]);
        Grader::create(['user_id' => $this->grader_user->id, 'section_id' => $this->section->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
    }

    /** @test */
    public function if_there_is_no_grader_email_gets_sent_to_the_owner()
    {
        DB::table('graders')->delete();
        $this->actingAs($this->student_user)
            ->getJson("/api/contact-grader-overrides/{$this->assignment->id}")
            ->assertJson(['default_grader_id' => $this->course->user_id]);
    }

    /** @test */
    public function if_there_is_a_grader_they_are_the_default_grader()
    {
        $this->actingAs($this->student_user)
            ->getJson("/api/contact-grader-overrides/{$this->assignment->id}")
            ->assertJson(['default_grader_id' => $this->grader_user->id]);

    }

    /** @test */
    public function if_there_is_an_override_grader_they_are_returned()
    {

        $grader_user_2 = factory(User::class)->create(['role' => 4]);
        DB::table('contact_grader_overrides')->insert(['course_id'=> $this->course->id,'user_id'=>$grader_user_2->id]);
        $this->actingAs($this->student_user)
            ->getJson("/api/contact-grader-overrides/{$this->assignment->id}")
            ->assertJson(['contact_grader_override_id' => $grader_user_2->id]);

    }

    /** @test */
    public function user_must_be_enrolled_in_the_course_to_send_an_email_to_the_grader()
    {
        $this->actingAs($this->student_user_2)
            ->getJson("/api/contact-grader-overrides/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to email one of the graders for this course.']);

    }

    /** @test */
    public function non_owner_cannot_update_grader_overrides()
    {

        $this->actingAs($this->user_2)
            ->patchJson("/api/contact-grader-overrides/{$this->course->id}", ['contact_grader_override' => null])
            ->assertJson(['message' => 'You are not allowed to update the grader contact information for that course.']);

    }

    /** @test */
    public function override_can_be_empty()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/contact-grader-overrides/{$this->course->id}", ['contact_grader_override' => null])
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function override_can_be_that_user()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/contact-grader-overrides/{$this->course->id}", ['contact_grader_override' => $this->user->id])
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function override_can_be_a_grader()
    {

        $this->actingAs($this->user)
            ->patchJson("/api/contact-grader-overrides/{$this->course->id}", ['contact_grader_override' => $this->grader_user->id])
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function override_must_be_section_grader_empty_or_owner()
    {

        $this->actingAs($this->user)
            ->patchJson("/api/contact-grader-overrides/{$this->course->id}", ['contact_grader_override' => $this->student_user->id])
            ->assertJson(['message' => 'You are not allowed to update the grader contact information to that user.']);
    }


}
