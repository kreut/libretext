<?php

namespace Tests\Feature;

use App\Course;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WebworkSubmissionErrorsTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
    }

    /** @test */
    public function only_admin_can_get_webwork_submission_errors()
    {
        $user = factory(User::class)->create(['id'=>20]);
        $this->actingAs($user)->getJson("/api/webwork/submission-errors")
            ->assertJson(['message' => 'You are not allowed to get the webwork submission errors.']);


    }
}
