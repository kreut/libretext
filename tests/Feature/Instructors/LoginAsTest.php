<?php

namespace Tests\Feature\Instructors;


use App\Question;
use App\User;
use Tests\TestCase;

class LoginAsTest extends TestCase
{

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        //create a student and enroll in the class
      $this->login_as_info = ['user' => $this->user_2->first_name . ' ' . $this->user_2->last_name .' --- ' . $this->user_2->email];

    }

    /** @test */
    public function cannot_get_all_users_if_you_have_an_incorrect_email()
    {
        $response = $this->actingAs($this->user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email'=> 'bogus'])
            ->get('/api/user/all');
        $this->assertEquals('You are not allowed to retrieve the users from the database.', $response->original['message']);
    }

    /** @test */
    public function  cannot_get_all_users_if_you_have_an_incorrect_cookie()
    {
        $this->user->email = 'me@me.com';
        $this->user->save();
        $response = $this->actingAs($this->user)
            ->withSession(['original_email'=> $this->user->email])
            ->get('/api/user/all');
        $this->assertEquals('You are not allowed to retrieve the users from the database.', $response->original['message']);
    }

    /** @test */
    public function can_get_all_users_if_you_have_a_correct_email_and_cookie()
    {
        $this->user->email = 'me@me.com';
        $this->user->save();
        $response = $this->actingAs($this->user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email'=> $this->user->email])
            ->get('/api/user/all');
        $this->assertEquals('success', $response->original['type']);
    }

    /** @test */
    public function cannot_login_as_another_user_if_you_have_an_incorrect_email()
    {
        $response = $this->actingAs($this->user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email'=> 'bogus'])
            ->post('/api/user/login-as', $this->login_as_info);
        $this->assertEquals('You are not allowed to log in as a different user.', $response->original['message']);
    }

    /** @test */
    public function cannot_login_as_another_user_if_you_have_an_incorrect_cookie()
    {
        $this->user->email = 'me@me.com';
        $this->user->save();
        $response = $this->actingAs($this->user)
            ->withSession(['original_email'=> $this->user->email])
            ->post('/api/user/login-as', $this->login_as_info);
        $this->assertEquals('You are not allowed to log in as a different user.', $response->original['message']);
    }

    /** @test */
    public function can_login_as_another_user_if_you_have_a_correct_email_and_cookie()
    {
        $this->user->email = 'me@me.com';
        $this->user->save();
        $response = $this->actingAs($this->user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->withSession(['original_email'=> $this->user->email])
            ->post('/api/user/login-as', $this->login_as_info);
        $this->assertEquals('success', $response->original['type']);
    }
}
