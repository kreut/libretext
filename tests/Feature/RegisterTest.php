<?php

namespace Tests\Feature;

use App\Grader;
use App\Section;
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
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        $this->section_2 = factory(Section::class)->create(['course_id' => $this->course->id,
            'name' => 'Section 2']);
        $this->valid_password = 'wefljkj7fljkWA@@ikll1';
    }

    public function register($password, $message)
    {
        $response['errors']['password'] = [$message];
        $this->postJson('/api/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.app',
            'student_id' => 'my student id',
            'password' => $password,
            'password_confirmation' => $password,
            'time_zone' => 'America/Los_Angeles',
            'registration_type' => 'student'
        ])
            ->assertJson($response);
    }

    /** @test */
    public function password_must_be_complex()
    {

        $this->register('1^sdflkjsdflksdflk',
            "The password must be at least 8 characters and contain at least one uppercase character.");
        $this->register('^sdflkjsdflksdflkAA',
            "The password must be at least 8 characters and contain at least one number.");
        $this->register('1sdflkjsdflksdflkAA',
            "The password must be at least 8 characters and contain at least one special character.");
        $this->register('^sdflkjsdflksdflk',
            "The password must be at least 8 characters and contain at least one uppercase character and one number.");
        $this->register('sdflkjsdflksdflk',
            "The password must be at least 8 characters and contain at least one uppercase character, one number, and one special character.");
        $this->register('!aI^3',
            "The password must be at least 8 characters.");
    }


    /** @test */
    public function can_register_as_student()
    {
        $this->postJson('/api/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.app',
            'student_id' => 'my student id',
            'password' => $this->valid_password,
            'password_confirmation' => $this->valid_password,
            'time_zone' => 'America/Los_Angeles',
            'registration_type' => 'student'
        ])
            ->assertSuccessful()
            ->assertJsonStructure(['id', 'first_name', 'last_name', 'email', 'time_zone']);
    }

    /** @test */
    public function can_register_as_grader_with_a_valid_access_code()
    {

        GraderAccessCode::create(['access_code' => 'a_valid_code', 'section_id' => $this->section->id]);
        GraderAccessCode::create(['access_code' => 'a_valid_code', 'section_id' => $this->section_2->id]);
        $response = $this->postJson('/api/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.app',
            'password' => $this->valid_password,
            'password_confirmation' => $this->valid_password,
            'registration_type' => 'grader',
            'time_zone' => 'America/Los_Angeles',
            'access_code' => 'a_valid_code'
        ]);

        $user_id = json_decode($response->content())->id;
        $sections = Grader::where('user_id', $user_id)->get()->pluck('section_id')->toArray();

        $this->assertEquals([$this->section->id, $this->section_2->id], $sections);

    }

    /** @test */
    public function cannot_register_as_grader_without_valid_access_code()
    {
        $this->postJson('/api/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.app',
            'password' => $this->valid_password,
            'password_confirmation' => $this->valid_password,
            'registration_type' => 'grader',
            'time_zone' => 'America/Los_Angeles',
            'access_code' => 'some bad code'
        ])
            ->assertJsonValidationErrors(['access_code']);
    }


    /** @test */
    public function can_not_register_with_existing_email()
    {
        factory(User::class)->create(['email' => 'test@test.app']);

        $this->postJson('/api/register', [
            'name' => 'Test User 2',
            'email' => 'test@test.app',
            'password' => $this->valid_password,
            'password_confirmation' => $this->valid_password,
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
            'password' => $this->valid_password,
            'password_confirmation' => $this->valid_password,
            'registration_type' => 'student',
            'time_zone' => 'some fake time zone'
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('time_zone');
    }

}
