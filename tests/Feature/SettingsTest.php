<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    /** @var \App\User */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
    }

    /** @test */

    public function first_name_must_be_valid(){
        $this->actingAs($this->user)
            ->patchJson('/api/settings/profile', [
                'first_name' => '',
                'last_name' => 'User',
                'email' => 'test@test.app',
            ])
            ->assertJsonValidationErrors(['first_name']);
    }
/** @test */
    public function last_name_must_be_valid(){
        $this->actingAs($this->user)
            ->patchJson('/api/settings/profile', [
                'first_name' => 'some name',
                'last_name' => '',
                'email' => 'test@test.app',
            ])
            ->assertJsonValidationErrors(['last_name']);
    }

    /** @test */
    public function time_zone_must_be_valid(){
        $this->actingAs($this->user)
            ->patchJson('/api/settings/profile', [
                'first_name' => 'some name',
                'last_name' => 'last name',
                'email' => 'test@test.app',
                'time_zone' => 'bad time zone'
            ])
            ->assertJsonValidationErrors(['time_zone']);
    }

    /** @test */
    public function email_must_be_valid(){
        $this->actingAs($this->user)
            ->patchJson('/api/settings/profile', [
                'first_name' => 'some name',
                'last_name' => 'last name',
                'email' => 'bad email',
            ])
            ->assertJsonValidationErrors(['email']);
    }
/** @test */
    public function update_profile_info()
    {
        $this->actingAs($this->user)
            ->patchJson('/api/settings/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@test.app',
                'time_zone' => 'America/New_York'
            ])
            ->assertSuccessful()
            ->assertJson(['type' =>'success']);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.app',
            'time_zone' => 'America/New_York'
        ]);
    }

/** @test */
    public function update_password()
    {
        $this->actingAs($this->user)
            ->patchJson('/api/settings/password', [
                'password' => 'updated',
                'password_confirmation' => 'updated',
            ])
            ->assertSuccessful();

        $this->assertTrue(Hash::check('updated', $this->user->password));
    }
}
