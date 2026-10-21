<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\User;
use Tests\TestCase;

class LibreOneAccessCodeTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['id' => 99]);//not admin
    }

    #[Test]
    public function cannot_get_user_by_access_code_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/libre-one-access-code/user/some-token")
            ->getContent();
        $this->assertEquals('{"type":"error","message":"You are not allowed to get the user by access code."}', $response);

    }
}
