<?php

namespace Tests\Feature\Admin;

use App\Traits\Test;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AccessCodeTest extends TestCase
{
    use Test;

    /**
     * @var Collection|Model|mixed
     */
    private $admin_user;
    /**
     * @var Collection|Model|mixed
     */
    private $user;
    /**
     * @var string[]
     */
    private $types;

    public function setup(): void
    {

        parent::setUp();
        $this->admin_user = factory(User::class)->create(['id' => 1]);//Admin
        $this->user = factory(User::class)->create(['id' => 9999]);//not Admin
        $this->types = ['instructor', 'non-instructor editor'];
    }

    private function getTable($type): string
    {
        switch ($type) {
            case('instructor'):
                $table = 'instructor_access_codes';
                break;
            case('non-instructor editor'):
                $table = 'question_editor_access_codes';
                break;
            case('tester'):
                $table = 'tester_access_codes';
                break;
            default:
                $table = 'not a valid type';
        }
        return $table;
    }

    /** @test */
    public function access_code_type_must_be_valid()
    {

        $this->actingAs($this->admin_user)->postJson('/api/access-code', [
            'number_of_access_codes' => 2,
            'type' => 'bogus type'])
            ->assertJson(['message' => 'bogus type is not a valid type of access code.']);
    }

    /** @test */
    public function non_admin_cannot_create_access_codes()
    {

        foreach ($this->types as $type) {
            $this->actingAs($this->user)->postJson('/api/access-code', [
                'number_of_access_codes' => 2,
                'type' => $type])
                ->assertJson(['message' => 'You are not allowed to get an access code.']);
        }

    }

    /** @test */
    public function admin_can_create_instructor_access_codes()
    {
        foreach ($this->types as $type) {

            $this->actingAs($this->admin_user)->postJson('/api/access-code', [
                'number_of_access_codes' => 2,
                'type' => $type])
                ->assertJson(['message' => 'The access codes have been created.']);
            $this->assertDatabaseCount($this->getTable($type), 2);
        }
    }

    /** @test */
    public function must_be_a_valid_number_of_instructor_access_codes()
    {
        foreach ($this->types as $type) {
            $this->actingAs($this->admin_user)->postJson('/api/access-code', [
                'number_of_access_codes' => 100,
                'type' => $type])
                ->assertJsonValidationErrors('number_of_access_codes');
        }
    }

    /** @test */
    public function non_admin_cannot_email_instructor_access_codes()
    {
        foreach ($this->types as $type) {
            $this->actingAs($this->user)->postJson('/api/access-code/email', [
                'email' => 'sdfds@hotmail.com',
                'type' => $type])
                ->assertJson(['message' => 'You are not allowed to email an access code.']);
        }

    }

    /** @test */
    public function email_must_be_legit()
    {
        foreach ($this->types as $type) {
            $this->actingAs($this->admin_user)->postJson('/api/access-code/email', [
                'email' => 'sdfds',
                'type' => $type])
                ->assertJsonValidationErrors('email');
        }

    }

    /** @test */
    public function email_must_not_already_be_in_database()
    {
        $this->actingAs($this->admin_user)->postJson('/api/access-code/email', [
            'email' => $this->user->email])
            ->assertJsonValidationErrors('email');

    }

    /** @test */
    public function admin_can_email_instructor_access_codes()
    {
        foreach ($this->types as $type) {
            $this->actingAs($this->admin_user)->postJson('/api/access-code/email', [
                'email' => 'some_new_email@hotmail.com',
                'type' => $type])
                ->assertJson(['type' => 'success']);
            $this->assertDatabaseCount($this->getTable($type), 1);
        }
    }
}
