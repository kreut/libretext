<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ToggleStudentViewTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['role'=> 3]);
    }

    /** @test */
    public function non_instructor_non_fake_student_cannot_toggle()
    {
        $this->actingAs($this->user)
            ->postJson("/api/user/toggle-student-view")
            ->assertJson(['message' => 'You are not allowed to toggle the student view.']);

    }
}
