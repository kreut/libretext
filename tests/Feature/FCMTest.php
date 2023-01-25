<?php

namespace Tests\Feature;

use App\Question;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FCMTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();

    }

    /** @test */
    public function can_store_fcm_token()
    {
        $this->actingAs($this->user)->postJson("/api/fcm-tokens",['fcm_token'=> 'Some token'])
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('fcm_tokens',['user_id'=> $this->user->id,'fcm_token'=>'Some token']);

    }
    /** @test */
    public function token_cannot_be_empty()
    {
        $this->actingAs($this->user)->postJson("/api/fcm-tokens",['fcm_token'=> ''])
            ->assertJson(['message' => 'No token in the request.']);

    }
}
