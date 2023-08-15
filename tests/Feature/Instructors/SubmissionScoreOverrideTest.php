<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Grader;
use App\Question;
use App\Section;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubmissionScoreOverrideTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);

        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->question = factory(Question::class)->create(['page_id' => 1695671]);
        $this->points = 10;
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => $this->points
        ]);

        $this->student_user_2 = factory(User::class)->create(['role' => 3]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        $this->submission_score_override = [
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'student_user_id' => $this->student_user->id,
            'question_title' => 'Some Title',
            'first_last' => 'Some name',
            'score' => 5];
    }


    /** @test */
    public function question_must_be_in_assignment()
    {
        $this->submission_score_override['question_id'] = 23412123;
        $this->actingAs($this->user)->patchJson("/api/submission-score-overrides", $this->submission_score_override)
            ->assertJsonValidationErrors('score');
    }

    /** @test */
    public function student_must_be_in_course()
    {
        DB::table('enrollments')->delete();
        $this->actingAs($this->user)->patchJson("/api/submission-score-overrides", $this->submission_score_override)
            ->assertJson(['message' => 'That student is not enrolled in your course.']);
    }


    /** @test */
    public function instructor_must_be_owner_of_course()
    {
        $user_2 = factory(User::class)->create();
        $this->actingAs($user_2)->patchJson("/api/submission-score-overrides", $this->submission_score_override)
            ->assertJson(['message' => 'The assignment is not in your course.']);

    }

    /** @test */
    public function score_must_be_valid()
    {
        $this->submission_score_override['score'] = 500;
        $this->actingAs($this->user)->patchJson("/api/submission-score-overrides", $this->submission_score_override)
            ->assertJson(['errors' => ['score' => ["The question is only worth $this->points points."]]]);

    }

    /** @test */
    public function instructor_can_override_submission_score()
    {
        $this->actingAs($this->user)->patchJson("/api/submission-score-overrides", $this->submission_score_override)
            ->assertJson(['message' => 'The score for Some name on question Some Title has been updated.']);

    }
}
