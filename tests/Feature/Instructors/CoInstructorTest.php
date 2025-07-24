<?php

namespace Tests\Feature\Instructors;

use App\Course;
use App\Traits\Test;
use App\User;
use Tests\TestCase;

class CoInstructorTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->user_3 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
    }

    /** @test **/
    public function non_main_instructor_cannot_change_main_instructor()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/courses/{$this->course->id}/change-main-instructor/{$this->user_3->id}")
            ->assertJson(['message' => "You are not allowed to change the main instructor for that course."]);
    }

    /** @test **/
    public function non_owner_cannot_delete_co_instructor()
    {
        $this->actingAs($this->user_2)
            ->deleteJson("/api/co-instructors/course/{$this->course->id}/co-instructor/{$this->user_3->id}")
            ->assertJson(['message' => "You are not allowed to remove a co-instructor from this course."]);
    }

    /** @test **/
    public function access_code_must_be_valid()
    {
        $this->actingAs($this->user_2)
            ->postJson("/api/co-instructors", ['access_code' => 'fake_code'])
            ->assertJson(['message' => "That does not appear to be a valid link.  Please ask the course instructor for another link."]);
    }
}

