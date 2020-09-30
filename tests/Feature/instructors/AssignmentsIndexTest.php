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
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

        $this->user_2 = factory(User::class)->create();
        $this->course_2  = factory(Course::class)->create(['user_id' => $this->user_2->id]);
        $this->assignment_2 = factory(Assignment::class)->create(['course_id' => $this->course_2->id]);

        $this->assignment_info = ['course_id' => $this->course->id,
            'name' => 'First Assignment',
            'available_from_date' => '2020-06-10',
            'available_from_time' => '09:00:00',
            'due_date' => '2020-06-12',
            'due_time' => '09:00:00',
            'scoring_type' => 'p',
            'default_points_per_question' => 2,
            'submission_files' => 'a'];

    }


    /** @test */

    public function must_be_of_a_valid_source() {



    }
/** @test **/
    public function will_only_update_the_name_and_dates_if_there_is_already_a_submission(){


    }


   /** @test */
    public function can_get_your_assignments()
    {

        $this->actingAs($this->user)->getJson("/api/assignments/courses/{$this->course->id}")
            ->assertJson([['name'=> 'First Assignment']]);

    }

    /** @test */

    public function owener_can_update_solutions_shown(){

    }

    /** @test */

    public function cannot_update_solutions_shown_if_not_owner(){

    }


    /** @test */
    public function must_submit_a_valid_scoring_type() {
        $this->markTestIncomplete(
            'check if c or p'
        );

    }

    /** @test */
    public function can_submit_scoring_type_completed() {
        $assignment_info = $this->assignment_info;
        unset($assignment_info['default_points_per_question']);
        $this->markTestIncomplete(
            'submit without the default points'
        );

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
    public function must_include_valid_default_points_per_question()
    {

        $this->assignment_info['default_points_per_question'] = "";
        $this->actingAs($this->user)->postJson("/api/assignments",$this->assignment_info)
            ->assertJsonValidationErrors(['default_points_per_question']);

        $this->assignment_info['default_points_per_question'] = "1.9";
        $this->actingAs($this->user)->postJson("/api/assignments",$this->assignment_info)
            ->assertJsonValidationErrors(['default_points_per_question']);

        $this->assignment_info['default_points_per_question'] = "10000";
        $this->actingAs($this->user)->postJson("/api/assignments",$this->assignment_info)
            ->assertJsonValidationErrors(['default_points_per_question']);

        $this->assignment_info['default_points_per_question'] = "-3";
        $this->actingAs($this->user)->postJson("/api/assignments",$this->assignment_info)
            ->assertJsonValidationErrors(['default_points_per_question']);
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

    /** @test */
    public function must_include_whether_files_are_allowed()
    {

        $this->assignment_info['submission_files'] = "7";
        $this->actingAs($this->user)->postJson("/api/assignments",$this->assignment_info)
            ->assertJsonValidationErrors(['submission_files']);
    }


}
