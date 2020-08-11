<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\User;
use App\Course;
use Tests\TestCase;

class InstructorsCourseTest extends TestCase
{


    public function can_get_your_courses() {

    }

    public function can_not_get_courses_of_another_user() {


    }


    public function can_delete_a_course_if_you_are_the_owner(){


    }

    public function cannot_delete_a_course_if_you_are_not_the_owner(){


    }


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

    /** @test */
    public function can_update_the_course_if_you_are_the_owner() {
        $user = factory(User::class)->create();
        $course = factory(Course::class)->create(['user_id' => $user->id,
            'name' => 'First Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10']);
        $this->actingAs($user)->patchJson("/api/courses/$course->id", [
            'name' => 'Some New Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10'
        ])->assertSuccessful()
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function cannot_update_a_course_if_you_are_not_the_owner(){
        //create two users
        factory(User::class, 2)->create();
        $user = User::find(2);
        $course = factory(Course::class)->create(['user_id' => 1,
            'name' => 'First Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10']);
        $this->actingAs($user)->patchJson("/api/courses/$course->id", [
            'name' => 'Some New Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10'
        ])->assertSuccessful()
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to update this course.']);


    }

    public function must_include_a_course_name(){

    }

    public function must_include_valid_dates() {

    }

    public function must_have_the_end_date_after_the_start_date(){


    }


}
