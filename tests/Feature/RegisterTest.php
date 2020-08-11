<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    /** @test */
    public function can_register()
    {
        $this->postJson('/api/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.app',
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'registration_type' => 'student'
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['id', 'first_name', 'last_name', 'email']);
    }

    /** @test */
    public function can_not_register_with_existing_email()
    {
        factory(User::class)->create(['email' => 'test@test.app']);

        $this->postJson('/api/register', [
            'name' => 'Test User 2',
            'email' => 'test@test.app',
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'registration_type' => 'student'
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
    }
}
