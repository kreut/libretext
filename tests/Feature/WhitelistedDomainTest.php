<?php

namespace Tests\Feature;

use App\Course;
use App\Question;
use App\User;
use App\WhitelistedDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WhitelistedDomainTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function cannot_get_whitelisted_domains_if_not_course_owner()
    {

        $this->actingAs($this->user_2)->getJson("/api/whitelisted-domains/{$this->course->id}", ['whitelisted_domain' => 'some domain'])
            ->assertJson(['message' => 'You are not allowed to get the whitelisted domains for that course.']);


    }

   /** @test */
    public function cannot_delete_whitelisted_domain_if_not_owner()
    {
        $whitelistedDomain = new WhitelistedDomain();
        $whitelistedDomain->course_id = $this->course->id;
        $whitelistedDomain->whitelisted_domain = 'someDomain';
        $whitelistedDomain->save();
        $this->actingAs($this->user_2)->deleteJson("/api/whitelisted-domains/$whitelistedDomain->id")
            ->assertJson(['message' => 'You are not allowed to delete a whitelisted domain from that course.']);
    }

    /** @test */
    public function cannot_store_whitelisted_domain_if_not_owner()
    {
        $this->actingAs($this->user_2)->postJson("/api/whitelisted-domains/{$this->course->id}", ['whitelisted_domain' => 'some domain'])
            ->assertJson(['message' => 'You are not allowed to store a whitelisted domain to that course.']);


    }
}
