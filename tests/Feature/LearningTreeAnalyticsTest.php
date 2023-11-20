<?php

namespace Tests\Feature;

use App\Course;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LearningTreeAnalyticsTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
    }

    /** @test */
    public function only_admin_can_get_learning_tree_analytics()
    {
        $user = factory(User::class)->create(['id'=>20]);
        $this->actingAs($user)->getJson("/api/learning-tree-analytics")
            ->assertJson(['message' => 'You are not allowed to get the Learning Tree analytics.']);


    }
}
