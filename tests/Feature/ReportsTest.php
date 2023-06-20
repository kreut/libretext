<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Grader;
use App\Question;
use App\RubricCategory;
use App\RubricCategorySubmission;
use App\Section;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['role' => 2]);//not admin
        $this->student_user = factory(User::class)->create(['role' => 3]);//not admin
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);

        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $section->id,
            'course_id' => $this->course->id
        ]);

        $this->grader_user = factory(User::class)->create();
        $this->grader_user->role = 4;
        Grader::create(['user_id' => $this->grader_user->id, 'section_id' => $section->id]);
        $this->question = factory(Question::class)->create(['page_id' => 18997376]);
        $this->rubric_category = RubricCategory::create(['question_id' => $this->question->id,
            'category' => 'some category',
            'criteria' => 'some criteria',
            'question_revision_id' => 0,
            'score' => 10,
            'order' => 1]);


        DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 0
        ]);
        $this->rubric_category_submission = RubricCategorySubmission::create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->student_user->id,
            'rubric_category_id' => $this->rubric_category->id,
            'submission' => 'some submission',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function grader_student_or_instructor_can_get_rubric_category_submission()
    {
        $this->actingAs($this->user)
            ->getJson("/api/rubric-category-submissions/assignment/{$this->assignment->id}/question/{$this->question->id}/user/{$this->student_user->id}")
            ->assertJson(['type' => 'success']);
        $this->actingAs($this->grader_user)
            ->getJson("/api/rubric-category-submissions/assignment/{$this->assignment->id}/question/{$this->question->id}/user/{$this->student_user->id}")
            ->assertJson(['type' => 'success']);
        $this->actingAs($this->student_user)
            ->getJson("/api/rubric-category-submissions/assignment/{$this->assignment->id}/question/{$this->question->id}/user/{$this->student_user->id}")
            ->assertJson(['type' => 'success']);

    }
    /** @test */
    public function must_be_owner_or_grader_to_test_new_rubric_criteria()
    {

        $this->actingAs($this->student_user)
            ->postJson("/api/rubric-category-submissions/{$this->rubric_category_submission->id}/test-rubric-criteria")
            ->assertJson(['message' => 'You are not allowed to test other rubric criteria.']);


    }

    /** @test */
    public function must_be_owner_or_grader_to_update_custom_feedback()
    {

        $this->actingAs($this->student_user)
            ->patchJson("/api/rubric-category-submissions/custom/{$this->rubric_category_submission->id}")
            ->assertJson(['message' => 'You are not allowed to update custom criteria.']);

    }

    /** @test */
    public function must_be_owner_or_grader_to_get_the_rubric_categories_for_an_assignment_question()
    {

        $this->actingAs($this->student_user)
            ->getJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/rubric-categories")
            ->assertJson(['message' => 'You are not allowed to get the rubric categories for that question in that assignment.']);

    }

    /** @test */
    public function must_have_token_to_receive_results_from_ai()
    {
        $this->postJson("/api/open-ai/results/lab-report")
            ->assertJson(['message' => 'Not authorized for processing the AI results using token: ']);

    }


    /** @test */
    public function only_student_with_valid_submission_credentials_can_store_rubric_category_submission()
    {
        $student_user_2 = factory(User::class)->create(['role' => 3]);
        $submission = ['rubric_category_id' => $this->rubric_category->id, 'submission' => 'blah blah'];
        $this->actingAs($student_user_2)
            ->patchJson("/api/rubric-category-submissions/{$this->rubric_category->id}/assignment/{$this->assignment->id}/question/{$this->question->id}", ['submission' => $submission])
            ->assertJson(['message' => 'No responses will be saved since you were not assigned to this assignment.']);

        $this->actingAs($this->student_user)
            ->patchJson("/api/rubric-category-submissions/{$this->rubric_category->id}/assignment/{$this->assignment->id}/question/{$this->question->id}", ['submission' => $submission])
            ->assertJson(['message' => 'No responses will be saved since you were not assigned to this assignment.']);

    }



    /** @test */
    public function non_grader_student_or_instructor_cannot_get_rubric_category_submission()
    {
        $student_user_2 = factory(User::class)->create(['role' => 3]);
        $this->actingAs($student_user_2)
            ->getJson("/api/rubric-category-submissions/assignment/{$this->assignment->id}/question/{$this->question->id}/user/{$this->student_user->id}")
            ->assertJson(['message' => 'You are not allowed to get these rubric category submissions.']);


    }


}
