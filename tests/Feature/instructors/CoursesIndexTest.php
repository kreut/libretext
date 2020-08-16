<?php

namespace Tests\Feature;

use App\CourseAccessCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\User;
use App\Course;
use Tests\TestCase;

class CoursesIndexTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create();
    }


    /** @test */
    public function can_get_your_courses()
    {

        factory(CourseAccessCode::class)->create(['access_code' => 'wefk;IOE',
            'course_id' => $this->course->id]);

        $this->actingAs($this->user)->getJson("/api/courses")
            ->assertJson(['courses' => [['id' => '1']]]);
    }

    /** @test */
    public function cannot_get_courses_if_student()

    {
        $this->user->role = 3;
        $this->actingAs($this->user)->getJson("/api/courses")
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to view courses.']);

    }

    /** @test */
    public function can_delete_a_course_if_you_are_the_owner()
    {

        $this->actingAs($this->user)->deleteJson("/api/courses/{$this->course->id}")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_delete_a_course_if_you_are_not_the_owner()
    {


        $course_2 = factory(Course::class)->create(['user_id' => $this->user_2->id]);
        $this->actingAs($this->user)->deleteJson("/api/courses/$course_2->id")
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to delete this course.']);


    }


    /** @test */
    public function can_create_a_course()
    {

        $this->actingAs($this->user)->postJson('/api/courses', [
            'name' => 'Some New Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10'
        ])->assertJson(['type' => 'success']);
    }

    /** @test */
    public function can_update_a_course_if_you_are_the_owner()
    {


        $this->actingAs($this->user)->patchJson("/api/courses/{$this->course->id}", [
            'name' => 'Some New Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10'
        ])->assertJson(['type' => 'success']);
    }

    /** @test */
    public function cannot_update_a_course_if_you_are_not_the_owner()
    {
        //create two users
        $this->actingAs($this->user_2)->patchJson("/api/courses/{$this->course->id}", [
            'name' => 'Some New Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10'
        ])->assertJson(['type' => 'error', 'message' => 'You are not allowed to update this course.']);


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
