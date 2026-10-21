<?php

namespace Tests\Feature\Admin;

use PHPUnit\Framework\Attributes\Test;
use App\User;
use Tests\TestCase;

class UserTest extends TestCase
{


    public function setup(): void
    {

        parent::setUp();
        $this->admin_user = factory(User::class)->create(['id' => 1, 'email' => 'me@me.com']);//Admin
        $this->user = factory(User::class)->create(['id' => 9999]);//not Admin
        $this->no_role_user = factory(User::class)->create(['role' => 0]);//not Admin

    }


    #[Test]
    public function non_admin_cannot_get_php_info()
    {
        $this->getJson("/api/php-info")
            ->assertJson(['message' => 'No access to php info.']);
    }


    #[Test]
    public function role_must_be_valid()
    {
        $this->actingAs($this->user)
            ->withSession(['original_email' => $this->admin_user->email])
            ->patch("/api/user/role", ['role' => 'bogus role'])
            ->assertJson(['message' => 'That is not a valid role.']);

    }
    #[Test]
    public function user_must_be_admin_to_update_role()
    {
        $this->actingAs($this->user)
            ->patch("/api/user/role", ['role' => 'bogus role'])
            ->assertJson(['message' => 'You are not allowed to update the user roles.']);

    }

    #[Test]
    public function user_must_be_admin_to_update_email()
    {
        $this->actingAs($this->user)
            ->patch("/api/user/email", ['email' => 'blah@blah.com'])
            ->assertJson(['message' => 'You are not allowed to update emails.']);

    }

}
