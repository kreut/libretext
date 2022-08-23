<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\User;
use Tests\TestCase;
use function factory;

class OpenCoursesTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->commons_user = factory(User::class)->create(['email' => 'commons@libretexts.org']);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id, 'public' => 0]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
    }


    /** @test */
    public function can_get_the_course_info_if_commons_course(){

        $this->course->user_id = $this->commons_user->id;
        $this->course->save();
        $this->actingAs($this->user)
            ->getJson("/api/courses/open/{$this->course->id}")
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function cannot_get_the_course_info_if_not_commons_course(){

        $this->actingAs($this->user)
            ->getJson("/api/courses/open/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to access this course.']);
    }

    /** @test */
    public function cannot_get_my_favorites_if_neither_public_nor_commons()
    {
        $this->actingAs($this->user_2)
            ->withSession(['anonymous_user' => true])
            ->getJson("/api/my-favorites/open-courses/{$this->assignment->id}")
            ->assertJson(['message' => 'You are not allowed to get the My Favorites questions for this assignment.']);
    }

    /** @test */
    public function can_get_favorites_if_public_and_instructor()
    {
        $this->course->public = 1;
        $this->course->save();
        $this->actingAs($this->user_2)
            ->getJson("/api/my-favorites/open-courses/{$this->assignment->id}")
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function can_get_favorites_if_commons_and_instructor()
    {
        $this->course->user_id = $this->commons_user->id;
        $this->course->save();

        $this->actingAs($this->user_2)
            ->getJson("/api/my-favorites/open-courses/{$this->assignment->id}")
            ->assertJson(['type' => 'success']);
    }


    /** @test */
    public function anonymous_user_can_view_open_course_if_public()
    {
        $this->course->public = 1;
        $this->course->save();

        $this->actingAs($this->user_2)
            ->withSession(['anonymous_user' => true])
            ->getJson("/api/assignments/courses/{$this->course->id}/anonymous-user")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function anonymous_user_can_view_open_course_if_commons()
    {
        $this->course->user_id = $this->commons_user->id;
        $this->course->save();

        $this->actingAs($this->user_2)
            ->withSession(['anonymous_user' => true])
            ->getJson("/api/assignments/courses/{$this->course->id}/anonymous-user")
            ->assertJson(['type' => 'success']);

    }


    /** @test */
    public function user_cannot_view_open_course_if_neither_commons_nor_public()
    {

        $this->actingAs($this->user_2)
            ->withSession(['anonymous_user' => true])
            ->getJson("/api/assignments/courses/{$this->course->id}/anonymous-user")
            ->assertJson(['message' => 'You are not allowed to view these assignments.']);
    }


    /** @test */
    public function can_get_public_assignments_using_public_parameter()
    {
        $this->course->public = 1;
        $this->course->save();
        $this->actingAs($this->user_2)->getJson("/api/assignments/open/public/{$this->course->id}")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function can_get_commons_assignments_using_commons_parameter()
    {
        $this->course->user_id = $this->commons_user->id;
        $this->course->save();
        $this->actingAs($this->user)->getJson("/api/assignments/open/commons/{$this->course->id}")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_get_non_commons_using_commons_parameter()
    {
        $this->actingAs($this->user)->getJson("/api/assignments/open/commons/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to access the assignments in that course since it is not a Commons course.']);
    }

    /** @test */
    public function cannot_get_non_public_assignments_using_public_parameter()
    {
        $this->actingAs($this->user)->getJson("/api/assignments/open/public/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to access the assignments in that course since it is not a Public course.']);
    }


}
