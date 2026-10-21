<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use App\Assignment;
use App\Course;

use App\User;
use Tests\TestCase;

class FormativeTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
    }

    #[Test]
    public function course_must_be_formative()
    {

        $this->actingAs($this->user)->getJson("/api/user/login-as-formative-student/assignment/{$this->assignment->id}")
            ->assertJson(['message' => 'This is not a formative assignment.']);

    }

    #[Test]
    public function creates_new_student()
    {

        $this->course->formative = 1;
        $this->course->save();
        $this->actingAs($this->user)->getJson("/api/user/login-as-formative-student/assignment/{$this->assignment->id}");
        $this->assertDatabaseHas('users', ['formative_student' => 1]);

    }


}
