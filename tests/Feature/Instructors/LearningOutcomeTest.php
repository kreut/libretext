<?php

namespace Tests\Feature\Instructors;

use App\User;
use Tests\TestCase;

class LearningOutcomeTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['role' => 3]);

    }

    /** @test */
    public function non_instructor_cannot_get_the_learning_outcomes()
    {
        $this->actingAs($this->user)
            ->getJson("/api/learning-outcomes/default-subject")
            ->assertJson(['message' => 'You are not allowed to retrieve a default subject from the database.']);
    }

    /** @test */
    public function non_instructor_cannot_get_the_default_subject()
    {
        $this->actingAs($this->user)
            ->getJson("/api/learning-outcomes/1")
            ->assertJson(['message' => 'You are not allowed to retrieve the learning outcomes from the database.']);

    }
}
