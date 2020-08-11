<?php

namespace Tests\Feature;

use App\CourseAccessCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\User;
use App\Course;
use Tests\TestCase;

class InstructorsCourseTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
    }


    /** @test */
    public function can_get_your_courses()
    {
        $course = factory(Course::class)->create(['user_id' => $this->user->id,
            'name' => 'First Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10']);
        factory(CourseAccessCode::class)->create(['access_code' => 'wefk;IOE',
            'course_id' => $course->id]);

        $this->actingAs($this->user)->getJson("/api/courses")
            ->assertSuccessful()
            ->assertJson(['courses' => [['id' => '1']]]);
    }
/** @test */
    public function can_not_get_courses_if_student()
    {
        $this->user->role = 3;
        $this->actingAs($this->user)->getJson("/api/courses")
            ->assertSuccessful()
            ->assertJson(['type' => 'error', 'message'=> 'You are not allowed to view courses.']);

    }

    /** @test */
    public function can_delete_a_course_if_you_are_the_owner()
    {
        $course = factory(Course::class)->create(['user_id' => $this->user->id,
            'name' => 'First Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10']);
        $this->actingAs($this->user)->deleteJson("/api/courses/$course->id")
            ->assertSuccessful()
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_delete_a_course_if_you_are_not_the_owner()
    {

        $user_2 = factory(User::class)->create();
        $course_2 = factory(Course::class)->create(['user_id' => $user_2->id,
            'name' => 'First Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10']);

        $this->actingAs($this->user)->deleteJson("/api/courses/$course_2->id")
            ->assertSuccessful()
            ->assertJson(['type' => 'error']);


    }


    /** @test */
    public function can_create_a_course()
    {

        $this->actingAs($this->user)->postJson('/api/courses', [
            'name' => 'Some New Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10'
        ])
            ->assertSuccessful()
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function can_update_the_course_if_you_are_the_owner()
    {

        $course = factory(Course::class)->create(['user_id' => $this->user->id,
            'name' => 'First Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10']);
        $this->actingAs($this->user)->patchJson("/api/courses/$course->id", [
            'name' => 'Some New Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10'
        ])->assertSuccessful()
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function cannot_update_a_course_if_you_are_not_the_owner()
    {
        //create two users
        $user_2 = factory(User::class)->create();
        $course = factory(Course::class)->create(['user_id' => $this->user->id,
            'name' => 'First Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10']);
        $this->actingAs($user_2)->patchJson("/api/courses/$course->id", [
            'name' => 'Some New Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10'
        ])->assertSuccessful()
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to update this course.']);


    }

    /** @test */
    public function must_include_a_course_name()
    {
        $this->actingAs($this->user)->postJson('/api/courses', [
            'name' => '',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10'
        ])->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function must_include_valid_start_date()
    {
        $this->actingAs($this->user)->postJson('/api/courses', [
            'name' => 'Some course',
            'start_date' => 'blah blah',
            'end_date' => '2021-06-10'
        ])->assertJsonValidationErrors(['start_date']);

    }

    /** @test */
    public function must_include_valid_end_date()
    {
        $this->actingAs($this->user)->postJson('/api/courses', [
            'name' => 'Some course',
            'start_date' => '2021-06-10',
            'end_date' => 'blah blah'
        ])->assertJsonValidationErrors(['end_date']);

    }

    /** @test */
    public function end_date_must_be_after_start_date()
    {
        $this->actingAs($this->user)->postJson('/api/courses', [
            'name' => 'Some course',
            'start_date' => '2021-06-10',
            'end_date' => '2021-06-09'
        ])->assertJsonValidationErrors(['end_date']);

    }


}
