<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\User;
use Tests\TestCase;

class LearningTreeAnalyticsTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
    }

    #[Test]
    public function only_admin_can_get_learning_tree_analytics()
    {
        $user = factory(User::class)->create(['id'=>20]);
        $this->actingAs($user)->getJson("/api/learning-tree-analytics")
            ->assertJson(['message' => 'You are not allowed to get the Learning Tree analytics.']);


    }
}
