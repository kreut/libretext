<?php

namespace Tests\Feature\Instructors;


use App\User;
use App\Course;
use Tests\TestCase;

class LetterGradesTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);


    }
/** @test */
    public function non_owner_cannot_toggle_show_z_scores()
    {
        $this->actingAs($this->user_2)->patchJson("/api/courses/{$this->course->id}/show-z-scores", ['show_z_scores' => 1])
            ->assertJson(['message' => 'You are not allowed to update being able to view the z-scores.']);

    }

    /** @test */
    public function owner_can_toggle_show_z_scores()
    {
        $this->actingAs($this->user)->patchJson("/api/courses/{$this->course->id}/show-z-scores", ['show_z_scores' => 1])
            ->assertJson(['message' => 'Students <strong>cannot</strong> view their z-scores.']);

    }


}
