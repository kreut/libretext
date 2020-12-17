<?php

namespace Tests\Feature\Instructors;

use App\Course;
use App\FinalGrade;
use App\Grader;
use App\User;
use App\Assignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AssignmentsIndex2Test extends TestCase
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
        $this->course_2 = factory(Course::class)->create(['user_id' => $this->user_2->id]);
        $this->assignment_2 = factory(Assignment::class)->create(['course_id' => $this->course_2->id]);

        $this->grader_user = factory(User::class)->create();
        $this->grader_user->role = 4;
        Grader::create(['user_id' => $this->grader_user->id, 'course_id' => $this->course->id]);
        $finalGrade = new FinalGrade();

        FinalGrade::create(['course_id' => $this->course->id,
            'letter_grades' => $finalGrade->defaultLetterGrades()]);


        $this->assignment_info = ['course_id' => $this->course->id,
            'name' => 'First Assignment',
            'available_from_date' => '2020-06-10',
            'available_from_time' => '09:00:00',
            'available_from' => '2020-06-10 09:00:00',
            'due_date' => '2020-06-12',
            'due_time' => '09:00:00',
            'due' => '2020-06-12 09:00:00',
            'scoring_type' => 'p',
            'source' => 'a',
            'default_points_per_question' => 2,
            'students_can_view_assignment_statistics' => 0,
            'include_in_weighted_average' => 1,
            'submission_files' => 'a',
            'instructions' => 'Some instructions',
            'assignment_group_id' => 1];

    }
    /** @test */
    public function non_owner_cannot_toggle_show_points_per_question()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/assignments/{$this->assignment->id}/show-points-per-question/1")
            ->assertJson(['message' => 'You are not allowed to show/hide the points per question.']);
    }

    /** @test */
    public function owner_can_toggle_show_points_per_question()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/show-points-per-question/1")
            ->assertJson(['message' => 'Your students <strong>cannot</strong> view the points per question.']);
    }

    public function can_update_an_assignment_if_you_are_the_owner()
    {
        $this->assignment_info['name'] = 'Some new name';
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
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJson(['type' => 'success']);

    }

    /** @test */

    public function must_be_of_a_valid_source()
    {
        $this->assignment_info['source'] = "";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['source']);


    }

    /** @test */
    public function must_include_an_assignment_name()
    {
        $this->assignment_info['name'] = "";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['name']);

    }

    /** @test */
    public function must_include_valid_available_on_date()
    {

        $this->assignment_info['available_from_date'] = "not a date";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['available_from_date']);

    }

    /** @test */
    public function must_include_valid_due_date()
    {
        $this->assignment_info['due_date'] = "not a date";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['due_date']);
    }

    /** @test */
    public function must_include_valid_default_points_per_question()
    {

        $this->assignment_info['default_points_per_question'] = "";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['default_points_per_question']);

        $this->assignment_info['default_points_per_question'] = "1.9";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['default_points_per_question']);

        $this->assignment_info['default_points_per_question'] = "10000";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['default_points_per_question']);

        $this->assignment_info['default_points_per_question'] = "-3";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['default_points_per_question']);
    }


    /** @test */
    public function must_include_valid_due_time()
    {
        $this->assignment_info['due_time'] = "not a time";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['due_time']);
    }

    /** @test */
    public function due_date_must_be_after_available_date()
    {
        $this->assignment_info['due'] = "1982-06-06";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJson(['message' => 'Your assignment should become due after it becomes available.']);
    }

    /** @test */
    public function must_include_valid_available_from_time()
    {

        $this->assignment_info['available_from_time'] = "not a time";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['available_from_time']);
    }

    /** @test */
    public function must_include_whether_files_are_allowed()
    {

        $this->assignment_info['submission_files'] = "7";
        $this->actingAs($this->user)->postJson("/api/assignments", $this->assignment_info)
            ->assertJsonValidationErrors(['submission_files']);
    }


}
