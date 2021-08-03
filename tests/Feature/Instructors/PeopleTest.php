<?php

namespace Tests\Feature\Instructors;

use App\Course;
use App\Grader;
use App\Section;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PeopleTest extends TestCase
{
    private $invite_info;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        $this->invite_info = ['course_id' => $this->course->id,
            'selected_sections' => [$this->section->id],
            'email' => 'me@me.com'];

        $this->grader_user = factory(User::class)->create();
        $this->grader_user->role = 4;
        Grader::create(['user_id' => $this->grader_user->id, 'section_id' => $this->section->id]);

    }

    /** @test */
    public function non_owner_cannot_invite_a_grader_to_a_section()
    {

        $this->actingAs($this->user_2)->postJson("/api/invitations/grader", $this->invite_info)
            ->assertJson(['message' => 'You are not allowed to invite graders to this course.']);

    }

    /** @test */
    public function commons_owner_cannot_invite_a_grader_to_a_section()
    {
        $this->user_2->email = 'commons@libretexts.org';
        $this->user_2->save();
        $this->actingAs($this->user_2)->postJson("/api/invitations/grader", $this->invite_info)
            ->assertJson(['message' => 'You are not allowed to invite graders to this course.']);

    }

    /** @test */
    public function email_must_be_valid()
    {
        $this->invite_info['email'] = 'bad email';
        $this->actingAs($this->user)->postJson("/api/invitations/grader", $this->invite_info)
            ->assertJsonValidationErrors(['email']);

    }

    /** @test */
    public function owner_can_invite_a_grader_to_a_section()
    {
        $this->actingAs($this->user)->postJson("/api/invitations/grader", $this->invite_info)
            ->assertJson(['message' => 'Your grader has been sent an email inviting them to this course.']);

    }

    /** @test */
    public function non_owner_cannot_update_grader_sections()
    {
        $this->actingAs($this->user_2)->patchJson("/api/graders/{$this->grader_user->id}", $this->invite_info)
            ->assertJson(['message' => "You are not allowed to update the grader's sections."]);

    }

    /** @test */
    public function owner_can_update_grader_sections()
    {
        $this->actingAs($this->user)->patchJson("/api/graders/{$this->grader_user->id}", $this->invite_info)
            ->assertJson(['message' => "The grader's sections have been updated."]);

    }

    /** @test */
    public function non_owner_cannot_remove_grader()
    {
        $this->actingAs($this->user_2)->deleteJson("/api/graders/{$this->course->id}/{$this->grader_user->id}")
            ->assertJson(['message' => "You are not allowed to remove this grader."]);

    }

    /** @test */
    public function owner_can_remove_grader()
    {
        $this->actingAs($this->user_2)->deleteJson("/api/graders/{$this->course->id}/{$this->grader_user->id}")
            ->assertJson(['message' => "You are not allowed to remove this grader."]);

    }


}
