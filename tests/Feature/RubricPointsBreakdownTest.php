<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Question;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RubricPointsBreakdownTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->question = factory(Question::class)->create(['page_id' => 24241]);

        $this->assignment_remixer = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        factory(Question::class)->create(['library' => 'chem', 'page_id' => 261531]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => '0'
        ]);
    }

    /** @test */
    public function non_owner_cannot_update_custom_rubric()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/update-custom-rubric")
            ->assertJson(['message' => 'You are not allowed to update the custom rubric for that question.']);
    }

    /** @test */
    public function non_owner_cannot_check_if_rubric_points_breakdown_exists()
    {
        $this->actingAs($this->user_2)
            ->getJson("/api/rubric-points-breakdown/assignment/{$this->assignment->id}/question/{$this->question->id}/exists")
            ->assertJson(['message' => 'You are not allowed to check if a rubric points breakdown exists for that assignment.']);

    }

    /** @test */
    public function non_owner_cannot_get_rubric_points_breakdown_by_assignment_user_question()
    {
        $student_user = factory(User::class)->create();
        $this->actingAs($this->user)
            ->getJson("/api/rubric-points-breakdown/assignment/{$this->assignment->id}/question/{$this->question->id}/user/$student_user->id")
            ->assertJson(['message' => 'You are not allowed to get the rubric points breakdown for that assignment-question-user.']);

    }
}
