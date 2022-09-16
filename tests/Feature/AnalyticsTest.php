<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['id' => 99]);//not admin
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
    }

    /** @test */
    public function cannot_get_learning_outcomes_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/analytics/learning-outcomes")
            ->getContent();
        $this->assertEquals('Not authorized.', $response);

    }

    /** @test */
    public function cannot_get_question_learning_outcome_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/analytics/question-learning-outcome")
            ->getContent();
        $this->assertEquals('Not authorized.', $response);
    }

    /** @test */
    public function cannot_get_scores_by_course_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/analytics/scores/course/{$this->course->id}")
            ->getContent();
        $this->assertEquals('{"error":"Not authorized."}', $response);
    }


    /** @test */
    public function cannot_get_review_history_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/analytics/review-history/assignment/{$this->assignment->id}")
            ->getContent();
        $this->assertEquals('Not authorized.', $response);
    }


    /** @test */
    public function cannot_data_shops_file_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/analytics")
            ->getContent();
        $this->assertEquals('Not authorized.', $response);

    }
}
