<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\User;
use Tests\TestCase;

class FCMTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();

    }

    #[Test]
    public function can_store_fcm_token()
    {
        $this->actingAs($this->user)->postJson("/api/fcm-tokens",['fcm_token'=> 'Some token'])
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('fcm_tokens',['user_id'=> $this->user->id,'fcm_token'=>'Some token']);

    }
    #[Test]
    public function token_cannot_be_empty()
    {
        $this->actingAs($this->user)->postJson("/api/fcm-tokens",['fcm_token'=> ''])
            ->assertJson(['message' => 'No token in the request.']);

    }
}
