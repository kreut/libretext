<?php

namespace Tests\Feature;

use App\Course;
use App\User;
use App\Assignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AssignmentsIndexTest extends TestCase
{

    /**Still must test the stuff with the correct/completed and number**/
    /** Should test that only an instructor can create an assignment... */
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create();
        $this->assignment = factory(Assignment::class)->create();

        $this->user_2 = factory(User::class)->create();
        $this->course_2  = factory(Course::class)->create(['user_id' => $this->user_2->id]);
        $this->assignment_2 = factory(Assignment::class)->create(['course_id' => $this->course_2->id]);

        $this->assignment_info = ['course_id' => $this->course->id,
            'name' => 'First Assignment',
            'available_from_date' => '2020-06-10',
            'available_from_time' => '09:00:00',
            'due_date' => '2020-06-12',
            'due_time' => '09:00:00',
            'num_submissions_needed' => 3,
            'type_of_submission' => 'correct'];

    }



   /** @test */
    public function can_get_your_assignments()
    {

        $this->actingAs($this->user)->getJson("/api/assignments/courses/{$this->course->id}")
            ->assertJson([['id'=> 1]]);

    }

    /** @test */
    public function cannot_get_assignments_if_you_are_a_student()
    {
        $this->user->role = 3;
        $this->actingAs($this->user)->getJson("/api/assignments/courses/{$this->course->id}")
            ->assertJson(['type' => 'error', 'message'=> 'You are not allowed to access this course.']);

    }

/** @test */
    public function can_delete_an_assignment_if_you_are_the_owner()
    {
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}")
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function cannot_delete_an_assignment_if_you_are_not_the_owner()
    {
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment_2->id}")
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to delete this assignment.']);

    }

/** @test */
    public function can_update_an_assignment_if_you_are_the_owner()
    {
        $this->assignment_info['name'] = "some new name";
        $this->actingAs($this->user)->patchJson("/api/assignments/{$this->assignment->id}",
            $this->assignment_info)
            ->assertJson(['type' => 'success']);
    }

/** @test */
    public function cannot_update_an_assignment_if_you_are_not_the_owner()
    {
        $this->assignment_info['name'] = "some other name";
        $this->actingAs($this->user_2)->patchJson("/api/assignments/{$this->assignment->id}",
        $this->assignment_info)->assertJson(['type' => 'error', 'message' => 'You are not allowed to update this assignment.']);
    }

/** @test */
    public function can_create_an_assignment()
    {
        $this->actingAs($this->user)->postJson("/api/assignments",$this->assignment_info)
            ->assertJson(['type' => 'success']);

    }

/** @test */
    public function must_include_an_assignment_name()
    {
        $this->assignment_info['name'] = "";
        $this->actingAs($this->user)->postJson("/api/assignments",$this->assignment_info)
            ->assertJsonValidationErrors(['name']);

    }

/** @test */
    public function must_include_valid_available_on_date()
    {

        $this->assignment_info['available_from_date'] = "not a date";
        $this->actingAs($this->user)->postJson("/api/assignments",$this->assignment_info)
            ->assertJsonValidationErrors(['available_from_date']);

    }

/** @test */
    public function must_include_valid_due_date()
    {
        $this->assignment_info['due_date'] = "not a date";
        $this->actingAs($this->user)->postJson("/api/assignments",$this->assignment_info)
            ->assertJsonValidationErrors(['due_date']);
    }

/** @test */
    public function must_include_valid_due_time()
    {
        $this->assignment_info['due_time'] = "not a time";
        $this->actingAs($this->user)->postJson("/api/assignments",$this->assignment_info)
            ->assertJsonValidationErrors(['due_time']);
    }

    /** @test */
    public function due_date_must_be_after_available_date()
    {
        $this->assignment_info['due_date'] = "1982-06-06";
        $this->actingAs($this->user)->postJson("/api/assignments",$this->assignment_info)
            ->assertJsonValidationErrors(['due_date']);
    }

/** @test */
    public function must_include_valid_available_from_time()
    {

        $this->assignment_info['available_from_time'] = "not a time";
        $this->actingAs($this->user)->postJson("/api/assignments",$this->assignment_info)
            ->assertJsonValidationErrors(['available_from_time']);
    }


}
