<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LtiRegistrationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->lti_registration_info = ['admin_name' => 'some admin',
            'lms' => 'canvas',
            'admin_email' => 'admin@admin.com',
            'url' => 'https://www.somewhere.com',
            'developer_key_id' => 23123312,
            'api_key' => 23423423,
            'api_secret' => '3r423423',
            'campus_id' => 'some_id',
            'school' => 'western washington university'];
    }

    /** @test */
    public function school_must_be_valid()
    {

        $this->postJson('/api/lti-registration/email-details', [
            'lms' => 'canvas',
            'school' => 'fake school'
        ])->assertJsonValidationErrors(['school']);
    }

    /** @test */
    public function campus_id_must_be_in_pending()
    {
        $this->postJson('/api/lti-registration/email-details', $this->lti_registration_info)
            ->assertJson(['message' => 'Your LTI Campus ID is not valid.  Please contact us for assistance.']);
    }

    /** @test */
    public function campus_id_must_not_be_used_already()
    {
        DB::table('lti_pending_registrations')->insert(['campus_id' => 'some_id']);
        DB::table('lti_keys')->insert(['id' => '1',
            'private_key_file' => '/mnt/local/lti/private.key',
            'alg' => 'RSA256']);
        DB::table('lti_registrations')->insert([
            'campus_id' => 'some_id',
            'admin_name' => 'eric kean',
            'admin_email' => 'someemail@sdfdsf.com',
            'iss' => 'https://canvas.instructure.com', 'auth_login_url' => 'https://wefek.instructure.com/api/lti/authorize_redirect',
            'auth_token_url' => 'https://wefek.instructure.com/login/oauth2/token', 'auth_server' => 'https://wefek.instructure.com',
            'client_id' => '123', 'key_set_url' => 'https://canvas.instructure.com/api/lti/security/jwks',
            'kid' => '1',
            'lti_key_id' => '1',
            'active' => '1']);

        $this->postJson('/api/lti-registration/email-details', $this->lti_registration_info)
            ->assertJson(['message' => 'This Campus ID is already in our system.  Please contact us for assistance.']);


    }
}
