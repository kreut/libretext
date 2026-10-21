<?php

namespace Tests\Feature\Admin;

use App\Traits\Test;
use Tests\TestCase;

class PayWallTest extends TestCase
{
    use Test;

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_update_the_license_status_without_an_appropriate_bearer_token()
    {
        $this->postJson("/api/enrollments/update-license-status")
            ->assertJson(['message' => "Missing Bearer Token."]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_get_the_latest_enrollment_date_without_an_appropriate_bearer_token()
    {
        $this->getJson("/api/enrollments/latest-enrollment-date")
            ->assertJson(['message' => "Missing Bearer Token."]);
    }
}
