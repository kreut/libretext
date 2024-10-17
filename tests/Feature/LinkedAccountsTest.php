<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LinkedAccountsTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['role' => 2]);//not admin
        /*
            Route::patch('/linked-account/validate-code', 'LinkedAccountController@validateCodeToLinkToAccount');
            Route::patch('/linked-account/switch/{account_to_switch_to}', 'LinkedAccountController@switch');
            Route::patch('/linked-account/unlink/{account_to_unlink}', 'LinkedAccountController@unlink');*/
    }

    /** @test */
    public function cannot_link_to_own_account()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/linked-account/email-validation-code", ['email' => $this->user->email])
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function cannot_link_to_non_instructor_account()
    {
        $student_user = factory(User::class)->create(['role' => 3]);
        $this->actingAs($this->user)
            ->patchJson("/api/linked-account/email-validation-code", ['email' => $student_user])
            ->assertJsonValidationErrors('email');

    }

    /** @test */
    public function cannot_link_to_admin_account()
    {
        $admin_user = factory(User::class)->create(['role' => 5]);
        $this->actingAs($this->user)
            ->patchJson("/api/linked-account/email-validation-code", ['email' => $admin_user->email])
            ->assertJsonValidationErrors('email');

    }

    /** @test */
    public function must_be_correct_validation_code()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/linked-account/validate-code", ['validation_code' => 'code does not exist'])
            ->assertJsonValidationErrors('validation_code');
    }

    /** @test */
    public function cannot_switch_to_invalid_account()
    {
        $user_2 = factory(User::class)->create();
        $this->actingAs($this->user)
            ->patchJson("/api/linked-account/switch/$user_2->id")
            ->assertJson(['message' => 'You cannot switch to that account.']);
    }

    /** @test */
    public function cannot_unlink_from_account_you_are_not_linked_to()
    {
        $user_2 = factory(User::class)->create();
        $this->actingAs($this->user)
            ->patchJson("/api/linked-account/unlink/$user_2->id")
            ->assertJson(['message' => 'You are not allowed to unlink that account.']);
    }


}
