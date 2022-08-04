<?php

namespace Tests\Feature\General;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ContactUsTest extends TestCase
{

    /** @test */
    public function cannot_send_to_random_user_if_not_logged_in()
    {

        $this->postJson('/api/email/send', [
            'name' => 'Some Name',
            'email' => 'some@email.com',
            'subject' => 'some subject',
            'text' => 'some text that I want to write',
            'to_user_id' => 6
        ])
            ->assertJson(['message' => 'We do not support that email type.']);

    }



    /** @test */
    public function name_is_required()
    {

        $this->postJson('/api/email/send', [
            'email' => 'some@email.com',
            'subject' => 'some subject',
            'text' => 'some text that I want to write',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

    }

    /** @test */
    public function subject_is_required()
    {

        $this->postJson('/api/email/send', [
            'email' => 'some@email.com',
            'name' => 'some name',
            'text' => 'some text that I want to write',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subject');

    }

    /** @test */
    public function email_must_be_valid()
    {

        $this->postJson('/api/email/send', [
            'subject' => 'some subject',
            'email' => 'someemail.com',
            'name' => 'some name',
            'text' => 'some text that I want to write',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

    }


    /** @test */
    public function can_send_contact_us_email() {


}

}
