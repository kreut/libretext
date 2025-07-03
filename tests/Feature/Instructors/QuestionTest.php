<?php

namespace Tests\Feature\Instructors;


use App\Assignment;
use App\Course;
use App\LearningTree;
use App\Question;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionTest extends TestCase
{

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        //create a student and enroll in the class
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->question = factory(Question::class)->create(['page_id' => 17652]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 0
        ]);
        $this->learning_tree = factory(LearningTree::class)->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function non_instructor_cannot_add_time_to_a_clicker()
    {
        $this->actingAs($this->student_user)->postJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/add-time")
            ->assertJson(['message' => 'You are not allowed to add time to this clicker assessment.']);

    }

    /** @test */
    public function non_instructor_cannot_customize_the_clicker_timing()
    {
        $this->actingAs($this->student_user)->patchJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/custom-clicker-time-to-submit",
        ['time_to_submit' => '30 seconds'])
            ->assertJson(['message' => 'You are not allowed to update the time to submit for this clicker assessment.']);

    }

    /** @test */
    public function customized_clicker_timing_must_be_valid()
    {
        $this->actingAs($this->user)->patchJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/custom-clicker-time-to-submit",
            ['time_to_submit' => '30 pizzas'])
            ->assertJsonValidationErrors('time_to_submit');


    }

    /** @test */
    public function non_instructor_cannot_restart_a_clicker_assessment()
    {
        $this->actingAs($this->student_user)->postJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/open-clicker")
            ->assertJson(['message' => 'You are not allowed to restart this clicker assessment.']);

    }

    /** @test */
    public function non_instructor_cannot_pause_a_clicker_assessment()
    {
        $this->actingAs($this->student_user)->patchJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/pause-clicker")
            ->assertJson(['message' => 'You are not allowed to pause this clicker assessment.']);
    }

    /** @test */
    public function non_instructor_cannot_resume_a_clicker_assessment()
    {
        $this->actingAs($this->student_user)->patchJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/resume-clicker")
            ->assertJson(['message' => 'You are not allowed to resume this clicker assessment.']);

    }


    /** @test */
    public function an_assignment_with_a_learning_tree_assessment_will_return_this_info()
    {

        //create a student and enroll in the class
        DB::table('assignment_question_learning_tree')->insertGetId([
            'assignment_question_id' => $this->assignment_question_id,
            'learning_tree_id' => $this->learning_tree->id,
            'number_of_successful_paths_for_a_reset' => 1
        ]);
        $response = $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/questions/summary");
        $this->assertEquals(true, $response['rows'][0]['learning_tree']);
    }

    /** @test */
    public function an_assignment_that_is_just_auto_graded_will_return_this_info()
    {


        $response = $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/questions/summary");
        $this->assertEquals(true, $response['rows'][0]['auto_graded_only']);

    }

    /** @test */
    public function an_assignment_that_is_not_just_auto_graded_will_return_this_info()
    {
        DB::table('assignment_question')->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->update(['open_ended_submission_type' => 'file']);
        $response = $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/questions/summary");
        $this->assertEquals(false, $response['rows'][0]['auto_graded_only']);

    }

    /** @test */
    public function a_student_cannot_view_the_question_view_page()
    {
        $this->actingAs($this->student_user)->getJson("/api/questions/{$this->question->id}")
            ->assertJson(['type' => 'error',
                'message' => "You are not allowed to retrieve the questions from the database."]);

    }

    /** @test */
    public function a_non_student_can_view_the_question_view_page()
    {
        $this->actingAs($this->user)->getJson("/api/questions/{$this->question->id}")
            ->assertJson(['type' => 'success']);

    }
}
