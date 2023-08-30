<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LibreOneAccessCodeTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['id' => 99]);//not admin
    }

    /** @test */
    public function cannot_get_user_by_access_code_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/libre-one-access-code/user/some-token")
            ->getContent();
        $this->assertEquals('{"type":"error","message":"You are not allowed to get the user by access code."}', $response);

    }
}
