<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Generator;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['id' => 99]);//not admin
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
    }

    /** @test */
    public function cannot_access_all_routes_with_analytics_key()
    {

        $token = \JWTAuth::claims(['analytics' => 1])->fromUser($this->user);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token])
            ->getJson("/api/case-study-notes/{$this->assignment->id}")
            ->assertJson(['message' => 'GET:CaseStudyNoteController@show is not authorized for analytics use.']);

    }
    /** @test */
    public function can_access_specific_routes()
    {

        $token = \JWTAuth::claims(['analytics' => 1])->fromUser($this->user);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token])
            ->getJson("/api/assignments/{$this->assignment->id}/get-questions-info")
            ->assertJson(['type' =>'success']);

    }

    /** @test */
    public function cannot_do_auto_log_in_without_bearer_token()
    {
        $this->getJson("/api/users/auto-login")
            ->assertJson(['message' => 'Missing Bearer Token.']);
    }

    /** @test */
    public function cannot_do_auto_log_in_without_valid_signature()
    {
        \JWTAuth::getJWTProvider()->setSecret('fake-secret');
        $token = \JWTAuth::getJWTProvider()->encode(['foo' => 'bar']);
        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token])
            ->getJson("/api/users/auto-login")
            ->assertJson(['message' => 'InvalidSignatureException: cannot log do auto-login.']);
    }

    /** @test */
    public function cannot_do_auto_log_in_without_valid_JWT()
    {
        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . 'garbage-jwt'])
            ->getJson("/api/users/auto-login")
            ->assertJson(['message' => 'JWT format is not valid.']);
    }

    /** @test */
    public function can_do_auto_log_in_with_valid_signature()
    {
        $key = new HmacKey(config('myconfig.analytics_token'));
        $signer = new HS256($key);
        $generator = new Generator($signer);
        $token = $generator->generate(['id' => 10]);
        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token])
            ->getJson("/api/users/auto-login")
            ->assertJson(['message' => '10 is not a valid user id.']);
    }

    /** @test */
    public function can_get_user_if_instructor()
    {
        $user = factory(User::class)->create(['id' => 2, 'role' => 2]);
        \JWTAuth::getJWTProvider()->setSecret(env('ANALYTICS_TOKEN'));
        $token = \JWTAuth::getJWTProvider()->encode(['id' => $user->id]);
        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token])
            ->getJson("/api/users/auto-login")
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function cannot_get_user_if_not_instructor()
    {
        $user = factory(User::class)->create(['id' => 2, 'role' => 3]);
        \JWTAuth::getJWTProvider()->setSecret(env('ANALYTICS_TOKEN'));
        $token = \JWTAuth::getJWTProvider()->encode(['id' => $user->id]);
        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token])
            ->getJson("/api/users/auto-login")
            ->assertJson(['message' => '2 is a user who is not an instructor.']);
    }

    /** @test */
    public function cannot_get_user_if_admin()
    {
        $user = factory(User::class)->create(['id' => 5]);
        \JWTAuth::getJWTProvider()->setSecret(env('ANALYTICS_TOKEN'));
        $token = \JWTAuth::getJWTProvider()->encode(['id' => $user->id]);
        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token])
            ->getJson("/api/users/auto-login")
            ->assertJson(['message' => '5 is an admin user.']);
    }


    /** @test */
    public function cannot_get_nursing_analytics_if_not_nursing_account()
    {

        $this->actingAs($this->user)->getJson("/api/analytics/nursing/0")
            ->assertJson(['message' => 'You are not allowed to view the nursing analytics.']);
    }

    /** @test */
    public function cannot_get_proportion_correct_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/analytics/proportion-correct-by-assignment/course/{$this->course->id}")
            ->getContent();
        $this->assertEquals('Not authorized to get proportion correct.', $response);

    }

    /** @test */
    public function cannot_get_learning_outcomes_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/analytics/learning-outcomes")
            ->getContent();
        $this->assertEquals('Not authorized.', $response);

    }

    /** @test */
    public function cannot_get_question_learning_outcome_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/analytics/question-learning-outcome")
            ->getContent();
        $this->assertEquals('Not authorized.', $response);
    }

    /** @test */
    public function cannot_get_scores_by_course_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/analytics/scores/course/{$this->course->id}")
            ->getContent();
        $this->assertEquals('{"error":"Not authorized."}', $response);
    }


    /** @test */
    public function cannot_get_review_history_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/analytics/review-history/assignment/{$this->assignment->id}")
            ->getContent();
        $this->assertEquals('Not authorized.', $response);
    }


    /** @test */
    public function cannot_data_shops_file_without_authorization()
    {

        $response = $this->actingAs($this->user)->getJson("/api/analytics")
            ->getContent();
        $this->assertEquals('Not authorized.', $response);

    }
}
