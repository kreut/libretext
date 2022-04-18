<?php

namespace Tests\Feature\Instructors;


use App\Assignment;
use App\AssignmentGroup;
use App\AssignmentGroupWeight;
use App\Enrollment;
use App\FinalGrade;
use App\Section;
use App\Traits\Test;
use App\User;
use App\Course;
use Tests\TestCase;

class LetterGradesTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;


        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);




        $finalGrade = new FinalGrade();

        FinalGrade::create(['course_id' => $this->course->id,
            'letter_grades' => $finalGrade->defaultLetterGrades()]);


    }


    /** @test */
    public function non_owner_cannot_toggle_students_can_view_weighted_average()
    {
        $this->actingAs($this->user_2)->patchJson("/api/courses/{$this->course->id}/students-can-view-weighted-average", ['students_can_view_weighted_average' => 1])
            ->assertJson(['message' => 'You are not allowed to update being able to view the weighted average.']);

    }

    /** @test */
    public function owner_cannot_toggle_students_can_view_weighted_average_if_weights_are_not_100()
    {
        $this->actingAs($this->user)->patchJson("/api/courses/{$this->course->id}/students-can-view-weighted-average", ['students_can_view_weighted_average' => 1])
            ->assertJson(['message' => 'Please first update your Assignment Group Weights so that the total weighting is equal to 100.']);

    }

    /** @test */
    public function owner_can_toggle_students_can_view_weighted_average_if_weights_are_100()
    {

        $assignmentGroup = AssignmentGroup::create(['assignment_group' => 'My Group',
            'course_id' => $this->course->id,
            'user_id' => $this->user->id]);
        AssignmentGroupWeight::create(['course_id' => $this->course->id,
            'assignment_group_id' => $assignmentGroup->id,
            'assignment_group_weight' => 100]);
        $this->assignment->assignment_group_id = $assignmentGroup->id;
        $this->assignment->save();

        $this->actingAs($this->user)->patchJson("/api/courses/{$this->course->id}/students-can-view-weighted-average", ['students_can_view_weighted_average' => 1])
            ->assertJson(['message' => 'Students <strong>cannot</strong> view their weighted averages.']);

    }


    /** @test */
    public function non_owner_cannot_toggle_show_progress_report()
    {
        $this->actingAs($this->user_2)->patchJson("/api/courses/{$this->course->id}/show-progress-report", ['show_progress_report' => 1])
            ->assertJson(['message' => 'You are not allowed to update being able to view the progress report.']);

    }

    /** @test */
    public function non_owner_cannot_toggle_show_z_scores()
    {
        $this->actingAs($this->user_2)->patchJson("/api/courses/{$this->course->id}/show-z-scores", ['show_z_scores' => 1])
            ->assertJson(['message' => 'You are not allowed to update being able to view the z-scores.']);

    }

    /** @test */
    public function cannot_toggle_show_z_scores_if_weights_do_not_equal_100()
    {

        $this->actingAs($this->user)->patchJson("/api/courses/{$this->course->id}/show-z-scores", ['show_z_scores' => 1])
            ->assertJson(['message' => 'Please first update your Assignment Group Weights so that the total weighting is equal to 100.']);

    }

    /** @test */
    public function owner_can_toggle_show_progress_reports()
    {
        $this->createAssignmentGroupWeightsAndAssignments();
        $this->actingAs($this->user)->patchJson("/api/courses/{$this->course->id}/show-progress-report", ['show_progress_report' => 1])
            ->assertJson(['message' => 'Students <strong>cannot</strong> view their progress reports.']);

    }
    /** @test */
    public function owner_can_toggle_show_z_scores()
    {
        $this->createAssignmentGroupWeightsAndAssignments();
        $this->actingAs($this->user)->patchJson("/api/courses/{$this->course->id}/show-z-scores", ['show_z_scores' => 1])
            ->assertJson(['message' => 'Students <strong>cannot</strong> view their z-scores.']);

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


}
