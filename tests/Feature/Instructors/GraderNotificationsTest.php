<?php

namespace Tests\Feature\Instructors;

use PHPUnit\Framework\Attributes\Test;
use App\Course;
use App\User;
use Tests\TestCase;

class GraderNotificationsTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();

        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
    }

    #[Test]
    public function num_reminders_per_week_must_be_valid(){
        $data = ['num_reminders_per_week' =>90];
        $this->actingAs($this->user)->patchJson("/api/grader-notifications/{$this->course->id}", $data)
            ->assertJsonValidationErrors(['num_reminders_per_week']);

    }


    #[Test]
    public function owner_can_update_grader_notifications(){
        $data = ['num_reminders_per_week' =>7];
        $this->actingAs($this->user)->patchJson("/api/grader-notifications/{$this->course->id}", $data)
            ->assertJson(['message' => 'Your Grader Notifications have been updated.']);

    }

#[Test]
    public function non_owner_cannot_get_grader_notifications(){
        $this->actingAs($this->user_2)->getJson("/api/grader-notifications/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to view the grader notifications for this course.']);

    }

    #[Test]
    public function owner_ca_get_grader_notifications(){
        $this->actingAs($this->user)->getJson("/api/grader-notifications/{$this->course->id}")
            ->assertJson(['type' => 'success']);

    }

    #[Test]
    public function non_owner_cannot_update_grader_notifications(){
        $this->actingAs($this->user_2)->patchJson("/api/grader-notifications/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to update the grader notifications for this course.']);

    }


}
