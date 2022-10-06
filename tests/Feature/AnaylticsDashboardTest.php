<?php

namespace Tests\Feature;

use App\AnalyticsDashboard;
use App\Assignment;
use App\Course;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AnaylticsDashboardTest extends TestCase
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
    public function database_updates_correctly()
    {
        $analytics_course_id = 'kIUUELlfi';
        AnalyticsDashboard::insert(['course_id' => $this->course->id,
            'shared_key' => 'some_key',
            'authorized' => 0,
            'analytics_course_id' => $analytics_course_id]);
        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . 'some_key',
        ])->post("/api/analytics-dashboard/sync/$analytics_course_id")
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('analytics_dashboards',
            ['authorized' => 1,
                'course_id' => $this->course->id,
                'shared_key' => '',
                'analytics_course_id' => $analytics_course_id]);
    }


    /** @test */
    public function valid_bearer_token_is_required_to_sync_analytics_dashboard()
    {
        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . 'abc',
        ])->post("/api/analytics-dashboard/sync/100")
            ->assertJson(['message' => 'The shared key abc does not exist.']);

    }


    /** @test */
    public function cannot_show_analytics_dashboard_if_not_course_owner()
    {
        $this->actingAs($this->user_2)->getJson("/api/analytics-dashboard/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to view the analytics dashboard for the course.']);
    }

    /** @test */
    public function bearer_token_is_required_to_sync_analytics_dashboard()
    {
        $this->postJson("/api/analytics-dashboard/sync/{$this->course->id}")
            ->assertJson(['message' => 'Missing a shared key in the request.']);
    }

}
