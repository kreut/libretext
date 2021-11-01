<?php

namespace Tests\Feature\Admin;

use App\Traits\Test;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InstructorAccessCodeTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->admin_user = factory(User::class)->create(['id' => 1]);//Admin
        $this->user = factory(User::class)->create(['id' => 9999]);//not Admin
    }

    /** @test */
    public function non_admin_cannot_create_instructor_access_codes()
    {

        $this->actingAs($this->user)->postJson('/api/instructor-access-code', [
            'number_of_instructor_access_codes' => 2])
            ->assertJson(['message' => 'You are not allowed to get an instructor access code.']);

    }

    /** @test */
    public function admin_can_create_instructor_access_codes()
    {
        $this->actingAs($this->admin_user)->postJson('/api/instructor-access-code', [
            'number_of_instructor_access_codes' => 2])
            ->assertJson(['message' => 'The instructor access codes have been created.']);
        $this->assertDatabaseCount('instructor_access_codes', 2);
    }

    /** @test */
    public function must_be_a_valid_number_of_instructor_access_codes()
    {

        $this->actingAs($this->admin_user)->postJson('/api/instructor-access-code', [
            'number_of_instructor_access_codes' => 100])
            ->assertJsonValidationErrors('number_of_instructor_access_codes');
    }

    /** @test */
    public function non_admin_cannot_email_instructor_access_codes()
    {
        $this->actingAs($this->user)->postJson('/api/instructor-access-code/email', [
            'email' => 'sdfds@hotmail.com'])
            ->assertJson(['message' => 'You are not allowed to email an access code.']);

    }

    /** @test */
    public function email_must_be_legit()
    {
        $this->actingAs($this->admin_user)->postJson('/api/instructor-access-code/email', [
            'email' => 'sdfds'])
            ->assertJsonValidationErrors('email');

    }

    /** @test */
    public function email_must_not_already_be_in_database()
    {
        $this->actingAs($this->admin_user)->postJson('/api/instructor-access-code/email', [
            'email' => $this->user->email])
            ->assertJsonValidationErrors('email');

    }

    /** @test */
    public function admin_can_email_instructor_access_codes()
    {
        $this->actingAs($this->admin_user)->postJson('/api/instructor-access-code/email', [
            'email' => 'some_new_email@hotmail.com'])
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseCount('instructor_access_codes', 1);
    }
}
