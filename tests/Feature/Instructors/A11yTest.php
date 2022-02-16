<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Section;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class A11yTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->student_user_2 = factory(User::class)->create(['role' => 3]);

        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
    }


    /** @test */
    public function non_instructor_cannot_update_a11y()
    {
        $this->actingAs($this->user_2)->patchJson("/api/enrollments/a11y",
            ['student_user_id' => $this->student_user->id,
                'course_id' => $this->course->id
            ])
            ->assertJson(['message' => 'You are not allowed to update a11y for this student.']);
    }

    /** @test */
    public function student_must_be_enrolled_in_the_course_to_udpate_a11y()
    {
        $this->actingAs($this->user)->patchJson("/api/enrollments/a11y",
            ['student_user_id' => $this->student_user_2->id,
                'course_id' => $this->course->id
            ])
            ->assertJson(['message' => 'You are not allowed to update a11y for this student.']);
    }

    /** @test */
    public function instructor_can_update_a11y()
    {
        $this->actingAs($this->user)->patchJson("/api/enrollments/a11y",
            ['student_user_id' => $this->student_user->id,
                'course_id' => $this->course->id
            ])
            ->assertJson(['message' => "{$this->student_user->first_name} {$this->student_user->last_name} will now be shown accessible questions."]);

    }


}
