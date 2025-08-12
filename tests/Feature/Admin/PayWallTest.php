<?php

namespace Tests\Feature\Admin;

use App\Traits\Test;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PayWallTest extends TestCase
{
    use Test;

    /** @test */
    public function cannot_update_the_license_status_without_an_appropriate_bearer_token()
    {
        $this->postJson("/api/enrollments/update-license-status")
            ->assertJson(['message' => "Missing Bearer Token."]);
    }

    /** @test */
    public function cannot_get_the_latest_enrollment_date_without_an_appropriate_bearer_token()
    {
        $this->getJson("/api/enrollments/latest-enrollment-date")
            ->assertJson(['message' => "Missing Bearer Token."]);
    }
}
