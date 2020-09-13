<?php

namespace Tests\Feature;

use App\User;
use App\Course;
use App\GraderAccessCode;

use Tests\TestCase;


class RegisterTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();

        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
    }


    /** @test */
    public function can_register_as_student()
    {
        $this->postJson('/api/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.app',
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'time_zone' => 'America/Los_Angeles',
            'registration_type' => 'student'
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['id', 'first_name', 'last_name', 'email', 'time_zone']);
    }

    /** @test */
    public function cannot_register_as_grader_without_valid_access_code()
    {
        $this->postJson('/api/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.app',
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'registration_type' => 'grader',
            'time_zone' => 'America/Los_Angeles',
            'access_code' => 'some bad code'
        ])
            ->assertJsonValidationErrors(['access_code']);
    }

    /** @test */
    public function can_register_as_grader_with_a_valid_access_code()
    {

        GraderAccessCode::create(['access_code' => 'a_valid_code', 'course_id' => $this->course->id]);
        $this->postJson('/api/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.app',
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'registration_type' => 'grader',
            'time_zone' => 'America/Los_Angeles',
            'access_code' => 'a_valid_code'
        ])
            ->assertJson(['role' => 4]);
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
            'registration_type' => 'student',
            'time_zone' => 'America/Los_Angeles'
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function can_not_register_with_invalid_time_zone()
    {


        $this->postJson('/api/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.app',
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'registration_type' => 'student',
            'time_zone' => 'some fake time zone'
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('time_zone');
    }

}
