<?php

namespace Tests\Feature\General;

use App\Enrollment;
use App\Question;
use App\Section;
use App\User;
use App\Course;


use Tests\TestCase;


class IncompleteRegistrationTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['role' => 0]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);


    }


    /** @test */
    public function non_zero_role_will_not_be_deleted()
    {
        $this->user->role = 1;
        $this->user->save();
        $this->actingAs($this->user)->deleteJson('/api/user')
            ->assertJson(['message' => 'You are an active user and cannot be removed from the database.']);
    }

    /** @test */
    public function instructor_with_course_will_not_be_deleted()
    {
        $this->course->user_id = $this->user->id;
        $this->course->save();
        $this->user->save();
        $this->actingAs($this->user)->deleteJson('/api/user')
            ->assertJson(['message' => 'You are an active user and cannot be removed from the database.']);
    }

    /** @test */
    public function student_enrolled_in_course_will_not_be_deleted()
    {
        $student_user = factory(User::class)->create(['role' => 0]);
        $section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $student_user->id,
            'section_id' => $section->id,
            'course_id' => $this->course->id
        ]);
        $this->actingAs($student_user)->deleteJson('/api/user')
            ->assertJson(['message' => 'You are an active user and cannot be removed from the database.']);
    }

    /** @test */
    public function non_instructor_question_editor_with_question_will_not_be_deleted()
    {
        $non_question_editor_user = factory(User::class)->create(['role' => 0]);
        factory(Question::class)->create([
            'page_id' => 12392101,
            'question_editor_user_id' => $non_question_editor_user->id
        ]);
        $this->actingAs($non_question_editor_user)->deleteJson('/api/user')
            ->assertJson(['message' => 'You are an active user and cannot be removed from the database.']);
    }

    /** @test */
    public function user_with_0_role_can_be_deleted()
    {

        $this->actingAs($this->user)->deleteJson('/api/user')
            ->assertJson(['message' => 'You are an active user and cannot be removed from the database.']);
    }

}
