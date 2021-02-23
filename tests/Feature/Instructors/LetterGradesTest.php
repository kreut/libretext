<?php

namespace Tests\Feature\Instructors;


use App\FinalGrade;
use App\User;
use App\Course;
use Tests\TestCase;

class LetterGradesTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $finalGrade = new FinalGrade();

        FinalGrade::create(['course_id' => $this->course->id,
            'letter_grades' => $finalGrade->defaultLetterGrades()]);



    }
/** @test */
    public function non_owner_cannot_toggle_show_z_scores()
    {
        $this->actingAs($this->user_2)->patchJson("/api/courses/{$this->course->id}/show-z-scores", ['show_z_scores' => 1])
            ->assertJson(['message' => 'You are not allowed to update being able to view the z-scores.']);

    }

    /** @test */
    public function owner_can_toggle_show_z_scores()
    {
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
