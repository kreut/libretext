<?php

namespace Tests\Feature;

use App\CourseAccessCode;
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
        $this->course = factory(Course::class)->create();

        $this->course_access_code = 'SomeCode';
        factory(CourseAccessCode::class)->create([
            'course_id' => $this->course->id,
            'access_code' =>    $this->course_access_code]);


    }
/** @test */
    public function can_get_enrollments_if_user_is_a_student(){


        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);


        $this->actingAs( $this->user)->getJson("/api/enrollments")
            ->assertJson([  'type' => 'error',
                'message' => 'You must be a student to view your enrollments.']);
    }



    /** @test */
    public function cannot_get_enrollments_if_user_is_not_a_student(){
        $this->actingAs($this->user)->getJson("/api/enrollments")
            ->assertJson([  'type' => 'error',
                'message' => 'You must be a student to view your enrollments.']);
    }
    /** @test */
    public function can_enroll_in_a_course_with_a_valid_access_code(){


        $this->actingAs($this->student_user)->postJson("/api/enrollments",[
            'course_id' => $this->course->id,
            'access_code' =>  $this->course_access_code
        ])->assertJson(['type' => 'success']);

    }

/** @test */
    public function cannot_enroll_in_a_course_with_an_invalid_access_code(){
        $this->actingAs($this->student_user)->postJson("/api/enrollments",[
            'course_id' => $this->course->id,
            'access_code' =>  'not the real code'
        ])->assertJsonValidationErrors(['access_code']);

    }

}
