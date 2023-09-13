<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PassbackByAssignmentTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);

        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

    }

    /** @test */
    public function non_owner_cannot_passback_by_assignment()
    {
        $this->actingAs($this->user_2)
            ->postJson("/api/passback-by-assignment/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to passback the grades by assignment.']);
    }

}
