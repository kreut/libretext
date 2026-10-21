<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\User;
use Tests\TestCase;

class WebworkSubmissionErrorsTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
    }

    #[Test]
    public function only_admin_can_get_webwork_submission_errors()
    {
        $user = factory(User::class)->create(['id'=>20]);
        $this->actingAs($user)->getJson("/api/webwork/submission-errors")
            ->assertJson(['message' => 'You are not allowed to get the webwork submission errors.']);


    }
}
