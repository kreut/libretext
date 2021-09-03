<?php

namespace Tests\Feature\Instructors;



use App\User;
use Tests\TestCase;

class AnonymousUserSessionTest extends TestCase
{

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
    }

    /** @test */
    public function cannot_set_anonymous_user_session_if_not_instructor()
    {
        $this->user->role =3;
        $this->actingAs($this->user)
            ->postJson('/api/users/set-anonymous-user-session')
            ->assertJson(['message' => 'You are not allowed to set an anonymous user session.']);

    }

    /** @test */
    public function instructors_can_set_anonymous_user_session()
    {
        $this->actingAs($this->user)
            ->postJson('/api/users/set-anonymous-user-session')
            ->assertJson(['type' => 'success']);

    }

}
