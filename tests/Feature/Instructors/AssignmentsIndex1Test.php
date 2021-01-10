<?php

namespace Tests\Feature\Instructors;

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



    public function letter_grades_error_message()
    {
        $response['errors']['letter_grades'] = ['This should be a comma separated list of numerical cutoffs with associated letters such as "90,A,80,B".  At least one cutoff should be 0; every other cutoff should be positive.  And, each letter grade and corresponding cutoff should be used only once.'];
        return $response;
    }

    /** @test */
    public function nonowner_cannot_update_letter_grades()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/final-grades/letter-grades/{$this->course->id}", ['letter_grades' => "90,'A',70,'B',0,'F'"])
            ->assertJson(['message' => 'You are not allowed do update letter grades.']);

    }

    /** @test */
    public function owner_can_update_letter_grades()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/final-grades/letter-grades/{$this->course->id}", ['letter_grades' => "90,'A',70,'B',0,'F'"])
            ->assertJson(['message' => 'Your letter grades have been updated.']);

    }


    /** @test */
    public function must_be_an_equal_number_of_letter_grades_and_cutoffs()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/final-grades/letter-grades/{$this->course->id}", ['letter_grades' => "90,'A',70"])
            ->assertJson($this->letter_grades_error_message());

    }

    /** @test */
    public function letter_grades_and_cutoffs_are_required()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/final-grades/letter-grades/{$this->course->id}", ['letter_grades' => ""])
            ->assertJson($this->letter_grades_error_message());

    }

    /** @test */
    public function cutoffs_must_be_numerical()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/final-grades/letter-grades/{$this->course->id}", ['letter_grades' => "90,'A','not a number','B'"])
            ->assertJson($this->letter_grades_error_message());

    }

    /** @test */
    public function there_should_be_at_least_one_zero_cutoff()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/final-grades/letter-grades/{$this->course->id}", ['letter_grades' => "90,'A',70,'B'"])
            ->assertJson($this->letter_grades_error_message());
    }

    /** @test */
    public function all_cutoff_should_be_positive()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/final-grades/letter-grades/{$this->course->id}", ['letter_grades' => "90,'A',-3,'B',0,'C'"])
            ->assertJson($this->letter_grades_error_message());

    }

    /** @test */
    public function letter_grades_should_not_be_repeated()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/final-grades/letter-grades/{$this->course->id}", ['letter_grades' => "90,'A',70,'A',0,'C'"])
            ->assertJson($this->letter_grades_error_message());

    }

    /** @test */
    public function cutoffs_should_not_be_repeated()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/final-grades/letter-grades/{$this->course->id}", ['letter_grades' => "90,'A',90,'B',0,'C'"])
            ->assertJson($this->letter_grades_error_message());

    }

    /** @test */
    public function non_owner_cannot_toggle_round_scores()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/final-grades/{$this->course->id}/round-scores/1")
            ->assertJson(['message' => 'You are not allowed do choose how scores are rounded.']);
    }

    /** @test */

    public function owner_can_toggle_round_scores()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/final-grades/{$this->course->id}/round-scores/1")
            ->assertJson(['message' => 'Scores <strong>will not</strong> be rounded up to the nearest integer.']);
    }

    /** @test */

    public function non_owner_cannot_release_letter_grades()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/final-grades/{$this->course->id}/release-letter-grades/1")
            ->assertJson(['message' => 'You are not allowed do update whether letter grades are released.']);
    }

    /** @test */

    public function owner_can_release_letter_grades()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/final-grades/{$this->course->id}/release-letter-grades/1")
            ->assertJson(['message' => 'The letter grades <strong>are not</strong> released.']);
    }

    /** @test */

    public function nonowner_cannot_get_course_letter_grades()
    {
        $this->actingAs($this->user_2)
            ->getJson("/api/final-grades/letter-grades/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to get the course letter grades.']);
    }

    /** @test */

    public function owner_can_get_course_letter_grades()
    {
        $response['letter_grades'][0] = ['letter_grade' => 'A', 'min' => '90%', 'max' => '-'];
        $this->actingAs($this->user)
            ->getJson("/api/final-grades/letter-grades/{$this->course->id}")
            ->assertJson($response);
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
