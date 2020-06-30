<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\User;
use Tests\TestCase;

class CourseTest extends TestCase
{
    /** @test */
    public function can_create_a_course()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user)->postJson('/api/courses', [
            'name' => 'Some New Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10'
        ])
            ->assertSuccessful()
            ->assertJson(['type' => 'success']);
    }



}
