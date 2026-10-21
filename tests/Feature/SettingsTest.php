<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use function factory;

class SettingsTest extends TestCase
{
    /** @var User */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
    }

    #[Test]
    public function anonymous_user_cannot_change_profile(){
        $this->user->email = 'anonymous';
        $this->user->save();
        $this->actingAs($this->user)
            ->patchJson('/api/settings/profile', [
                'first_name' => 'some other name',
                'last_name' => 'some other last name',
                'email' => 'some@other-email.com',
            ])
            ->assertJson(['message' => 'You are not allowed to update the profile.']);
    }



    #[Test]
    public function first_name_must_be_valid(){
        $this->actingAs($this->user)
            ->patchJson('/api/settings/profile', [
                'first_name' => '',
                'last_name' => 'User',
                'email' => 'test@test.app',
            ])
            ->assertJsonValidationErrors(['first_name']);
    }
#[Test]
    public function last_name_must_be_valid(){
        $this->actingAs($this->user)
            ->patchJson('/api/settings/profile', [
                'first_name' => 'some name',
                'last_name' => '',
                'email' => 'test@test.app',
            ])
            ->assertJsonValidationErrors(['last_name']);
    }

    #[Test]
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

    #[Test]
    public function email_must_be_valid(){
        $this->actingAs($this->user)
            ->patchJson('/api/settings/profile', [
                'first_name' => 'some name',
                'last_name' => 'last name',
                'email' => 'bad email',
            ])
            ->assertJsonValidationErrors(['email']);
    }


#[Test]
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

#[Test]
    public function update_password_must_be_complex()
    {
        ///the complexity is tested when registering.
        $this->actingAs($this->user)
            ->patchJson('/api/settings/password', [
                'password' => 'updated',
                'password_confirmation' => 'updated',
            ])
            ->assertJsonValidationErrors('password');

    }

    #[Test]
    public function can_update_password()
    {
        ///the complexity is tested when registering.
        $this->actingAs($this->user)
            ->patchJson('/api/settings/password', [
                'password' => 'updated!A1',
                'password_confirmation' => 'updated!A1',
            ])
            ->assertSuccessful();

        $this->assertTrue(Hash::check('updated!A1', $this->user->password));
    }
}
