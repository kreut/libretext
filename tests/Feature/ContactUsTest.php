<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ContactUsTest extends TestCase
{
    /** @test */
    public function name_is_required()
    {

        $this->postJson('/api/contact-us', [
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

        $this->postJson('/api/contact-us', [
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

        $this->postJson('/api/contact-us', [
            'subject' => 'some subject',
            'email' => 'someemail.com',
            'name' => 'some name',
            'text' => 'some text that I want to write',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

    }

    /** @test */
    public function text_must_be_at_least_10_characters()
    {

        $this->postJson('/api/contact-us', [
            'subject' => 'some subject',
            'email' => 'some@email.com',
            'name' => 'some name',
            'text' => 'some ',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('text');

    }

    /** @test */
    public function can_send_contact_us_email() {


}

}
