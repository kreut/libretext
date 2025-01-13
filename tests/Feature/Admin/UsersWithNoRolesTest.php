<?php

namespace Tests\Feature\Admin;

use App\User;
use Tests\TestCase;

class UsersWithNoRolesTest extends TestCase
{


    public function setup(): void
    {

        parent::setUp();
        $this->admin_user = factory(User::class)->create(['id' => 1, 'email' => 'me@me.com']);//Admin
        $this->user = factory(User::class)->create(['id' => 9999]);//not Admin
        $this->no_role_user = factory(User::class)->create(['role' => 0]);//not Admin

    }

    /** @test */
    public function role_must_be_valid()
    {
        $this->actingAs($this->admin_user)
            ->patchJson("/api/users-with-no-role/{$this->no_role_user->id}", ['role' => 'bogus role'])
            ->assertJson(['message' => 'bogus role is an invalid role.']);

    }

    /** @test */
    public function updated_user_will_have_correct_role()
    {
        $this->actingAs($this->admin_user)
            ->patchJson("/api/users-with-no-role/{$this->no_role_user->id}", ['role' => 'student'])
            ->assertJson(['type' => 'success']);
        $user = User::find($this->no_role_user->id);
        $this->assertEquals(3, $user->role);
    }


    /** @test */
    public function delete_user_must_have_no_role()
    {

        $this->actingAs($this->admin_user)
            ->delete("/api/users-with-no-role/{$this->user->id}")
            ->assertJson(['message' => 'You cannot delete the user since they already have a role.']);
    }

    /** @test */
    public function admin_can_delete_user()
    {
        $this->assertDatabaseCount('users', 3);
        $this->actingAs($this->admin_user)
            ->delete("/api/users-with-no-role/{$this->no_role_user->id}")
            ->assertJson(['type' => 'info']);
        $this->assertDatabaseCount('users', 2);

    }


    /** @test */
    public function must_be_admin_with_cookie_to_get_users_with_no_roles()
    {
        $this->actingAs($this->user)->getJson('/api/users-with-no-role')
            ->assertJson(['message' => 'You are not allowed to get the users without roles.']);
    }

    /** @test */
    public function admin_can_get_users_with_no_roles()
    {

        $response = $this->actingAs($this->admin_user)
            ->get('/api/users-with-no-role')
            ->content();
        $this->assertCount(1, json_decode($response)->users_with_no_role);

    }


}
