<?php

namespace Tests\Feature\Students;

use App\CourseAccessCode;
use App\Section;
use App\Traits\AccessCodes;
use App\User;
use App\Course;
use App\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StudentsCoursesIndexTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        $this->section_1 = factory(Section::class)->create(['course_id' => $this->course->id,
            'name'=>'Section 2',
            'access_code' =>'some_other_access_code']);

        $this->course_2 = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section_2 = factory(Section::class)->create(['course_id' => $this->course_2->id]);


    }

    /** @test */
    public function can_get_enrollments_of_shown_courses_if_user_is_a_student()
    {
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id,
            'section_id' => $this->section->id
        ]);

        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course_2->id,
            'section_id' => $this->section_2->id
        ]);

        $this->course_2->shown = 0;
        $this->course_2->save();


        $response = $this->actingAs($this->student_user)->getJson("/api/enrollments");
        $this->assertEquals(1, count($response->original['enrollments']));

    }


    /** @test */
    public function can_get_enrollments_if_user_is_a_student()
    {


        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id,
            'section_id' => $this->section->id
        ]);


        $this->actingAs($this->student_user)->getJson("/api/enrollments")
            ->assertJson(['type' => 'success']);
    }


    /** @test */
    public function cannot_get_enrollments_if_user_is_not_a_student()
    {
        $this->actingAs($this->user)->getJson("/api/enrollments")
            ->assertJson(['type' => 'error',
                'message' => 'You must be a student to view your enrollments.']);
    }

    /** @test */
    public function can_enroll_in_a_course_with_a_valid_access_code()
    {


        $this->actingAs($this->student_user)->postJson("/api/enrollments", [
            'section_id' => $this->section->id,
            'access_code' => $this->section->access_code
        ])->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_enroll_in_more_than_one_section_of_a_course()
    {
        $enrollment = new Enrollment();
        $enrollment->user_id = $this->student_user->id;
        $enrollment->course_id = $this->course->id;
        $enrollment->section_id = $this->section->id;
        $enrollment->save();

        $this->actingAs($this->student_user)->postJson("/api/enrollments", [
            'section_id' => $this->section_1->id,
            'access_code' => $this->section_1->access_code
        ])->assertJson(['message' => 'You are already enrolled in another section of this course.']);

    }

    /** @test */
    public function cannot_enroll_in_a_course_with_an_invalid_access_code()
    {
        $this->actingAs($this->student_user)->postJson("/api/enrollments", [
            'section_id' => $this->section->id,
            'access_code' => 'not the real code'
        ])->assertJsonValidationErrors(['access_code']);

    }

}
