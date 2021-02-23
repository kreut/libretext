<?php

namespace Tests\Feature\Instructors;

use App\AssignmentGroupWeight;
use App\Course;
use App\FinalGrade;
use App\Grader;
use App\User;
use App\Assignment;
use App\Extension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AssignmentsIndex1Test extends TestCase
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

        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;

        $this->grader_user = factory(User::class)->create();
        $this->grader_user->role = 4;
        Grader::create(['user_id' => $this->grader_user->id, 'course_id' => $this->course->id]);

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

    public function owner_cannot_release_letter_grades_if_assignment_weights_are_not_100()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/final-grades/{$this->course->id}/release-letter-grades/1")
            ->assertJson(['message' => 'Please first update your Assignment Group Weights so that the total weighting is equal to 100.']);
    }

    /** @test */

    public function owner_can_release_letter_grades()
    {

        AssignmentGroupWeight::create(['course_id' => $this->course->id,
            'assignment_group_id' => $this->assignment_info['assignment_group_id'],
            'assignment_group_weight' => 100]);
        $this->actingAs($this->user)
            ->patchJson("/api/final-grades/{$this->course->id}/release-letter-grades/1")
            ->assertJson(['message' => 'The letter grades <strong>are not</strong> released.']);
    }

    /** @test */

    public function an_owner_is_warned_before_showing_scores_if_there_is_an_active_extension()
    {
        Extension::create(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'extension' => '2040-01-01 00:00:00']);
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/show-scores/0")
            ->assertJson(['message' => "Your students <strong>can</strong> view their scores.  <br><br>Please note that at least one of your students has an active extension and they can potentially view other students' scores and grader comments."]);

    }

    /** @test */

    public function an_owner_is_warned_before_releasing_solutions_if_there_is_an_active_extension()
    {
        Extension::create(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'extension' => '2040-01-01 00:00:00']);
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/solutions-released/0")
            ->assertJson(['message' => "The solutions have been <strong>released</strong>.  <br><br>Please note that at least one of your students has an active extension and they can potentially view the solutions."]);

    }


    /** @test */
    public function non_owner_cannot_toggle_showing_assignments()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/assignments/{$this->assignment->id}/show-assignment/1")
            ->assertJson(['message' => 'You are not allowed to toggle whether students can view an assignment.']);
    }

    /** @test */

    public function owner_can_toggle_showing_assignments()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/show-assignment/1")
            ->assertJson(['message' => 'Your students <strong>cannot</strong> see this assignment.']);
    }




    /** @test */
    public function nonowner_cannot_toggle_showing_assignment_statistics()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/assignments/{$this->assignment->id}/show-assignment-statistics/1")
            ->assertJson(['message' => 'You are not allowed to show/hide assignment statistics.']);
    }

    /** @test */
    public function owner_can_toggle_showing_assignment_statistics()
    {

        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/show-assignment-statistics/1")
            ->assertJson(['message' => 'Your students <strong>cannot</strong> view the assignment statistics.']);
    }


    /** @test */
    public function nonowner_cannot_create_new_assignment_group()
    {

        $this->actingAs($this->user_2)
            ->postJson("/api/assignmentGroups/{$this->course->id}", ['assignment_group' => 'some group'])
            ->assertJson(['message' => 'You are not allowed to create an assignment group for this course.']);
    }

    /** @test */

    public function owner_can_create_new_assignment_group()
    {
        $this->actingAs($this->user)
            ->postJson("/api/assignmentGroups/{$this->course->id}", ['assignment_group' => 'some group'])
            ->assertJson(['message' => '<strong>some group</strong> has been added as an assignment group.']);
    }


    /** @test */

    public function assignment_group_must_not_be_empty()
    {
        $this->actingAs($this->user)
            ->postJson("/api/assignmentGroups/{$this->course->id}", ['assignment_group' => ''])
            ->assertJsonValidationErrors(['assignment_group']);
    }


    /** @test */

    public function a_course_grader_can_show_scores()
    {
        $this->actingAs($this->grader_user)
            ->patchJson("/api/assignments/{$this->assignment->id}/show-scores/0")
            ->assertJson(['message' => 'Your students <strong>can</strong> view their scores.  ']);
    }

    /** @test */

    public function a_course_grader_can_release_solutions()
    {
        $this->actingAs($this->grader_user)
            ->patchJson("/api/assignments/{$this->assignment->id}/solutions-released/0")
            ->assertJson(['message' => 'The solutions have been <strong>released</strong>.  ']);
    }


    /** @test */

    public function non_owner_non_grader_cannot_show_solutions_release_scores()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/assignments/{$this->assignment->id}/show-scores/0")
            ->assertJson(['message' => 'You are not allowed to show/hide scores.']);

        $this->actingAs($this->user_2)
            ->patchJson("/api/assignments/{$this->assignment->id}/solutions-released/0")
            ->assertJson(['message' => 'You are not allowed to show/hide solutions.']);
    }


    /** @test */

    public function a_course_owner_can_show_scores()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/show-scores/0")
            ->assertJson(['message' => 'Your students <strong>can</strong> view their scores.  ']);
    }

    /** @test */

    public function a_course_owner_can_release_solutions()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/solutions-released/0")
            ->assertJson(['message' => 'The solutions have been <strong>released</strong>.  ']);
    }

    /** @test * */
    public function will_only_update_the_name_and_dates_if_there_is_already_a_submission()
    {


    }


    /** @test */
    public function can_get_your_assignments()
    {
        $expected['assignments'][0]['name'] = 'First Assignment';
        $this->actingAs($this->user)->getJson("/api/assignments/courses/{$this->course->id}")
            ->assertJson($expected);

    }

    /** @test */

    public function owener_can_update_solutions_shown()
    {

    }

    /** @test */

    public function cannot_update_solutions_shown_if_not_owner()
    {

    }


    /** @test */
    public function must_submit_a_valid_scoring_type()
    {
        $this->markTestIncomplete(
            'check if c or p'
        );

    }

    /** @test */
    public function can_submit_scoring_type_completed()
    {
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
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to access this course.']);

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


}
