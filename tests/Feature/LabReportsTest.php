<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Grader;
use App\Question;
use App\RubricCategory;
use App\Section;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LabReportsTest extends TestCase
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
        $this->rubric_category = RubricCategory::create(['assignment_id' => $this->assignment->id,
            'category' => 'some category',
            'criteria' => 'some criteria',
            'percent' => 10,
            'order' => 1]);

        $this->question = factory(Question::class)->create(['page_id' => 18997376]);

        DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 0
        ]);
    }

    /** @test */
    public function non_owner_cannot_get_the_purpose_of_the_report()
    {
        $this->actingAs($this->student_user)
            ->getJson("/api/assignments/{$this->assignment->id}/purpose")
            ->assertJson(['message' => 'You are not allowed to retrieve the purpose of this assignment.']);


    }

    /** @test */
    public function owner_or_grader_can_get_the_purpose_of_a_report()
    {
        $this->actingAs($this->student_user)
            ->getJson("/api/assignments/{$this->assignment->id}/purpose")
            ->assertJson(['message' => 'You are not allowed to retrieve the purpose of this assignment.']);

    }

    /** @test */
    public function non_owner_cannot_update_the_purpose_of_a_report()
    {
        $this->actingAs($this->student_user)
            ->patchJson("/api/assignments/{$this->assignment->id}/purpose", ['purpose' => 'some purpose'])
            ->assertJson(['message' => 'You are not allowed to update the purpose of this assignment.']);

    }

    /** @test */
    public function owner_or_grader_can_update_the_purpose_of_a_report()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/purpose", ['purpose' => 'some purpose'])
            ->assertJson(['message' => 'The purpose has been updated.']);
        $this->actingAs($this->grader_user)
            ->patchJson("/api/assignments/{$this->assignment->id}/purpose", ['purpose' => 'some purpose'])
            ->assertJson(['message' => 'The purpose has been updated.']);

    }


    /** @test */
    public function must_have_token_to_receive_results_from_ai()
    {
        $this->postJson("/api/open-ai/results/lab-report")
            ->assertJson(['message' => 'Not authorized for processing the AI results.']);

    }

    /** @test */
    public function non_owner_cannot_order_the_rubric()
    {

        $this->actingAs($this->student_user)
            ->patchJson("/api/rubric-categories/{$this->assignment->id}/order", ['ordered_rubric_categories' => [$this->rubric_category->id]])
            ->assertJson(['message' => 'You are not allowed to re-order the rubric categories for this assignment.']);
    }


    /** @test */
    public function non_owner_cannot_store_the_rubric()
    {
        $rubric_category_info = [
            'assignment_id' => $this->assignment->id,
            'category' => 'mickey mouse',
            'criteria' => 'some criteria',
            'percent' => 10];

        $this->actingAs($this->student_user)
            ->postJson("/api/rubric-categories", $rubric_category_info)
            ->assertJson(['message' => 'You are not allowed to save a rubric category for this assignment.']);
    }

    /** @test */
    public function categories_cannot_be_repeated()
    {
        $rubric_category_info = [
            'assignment_id' => $this->assignment->id,
            'category' => $this->rubric_category->category,
            'criteria' => 'some criteria',
            'percent' => 10];

        $this->actingAs($this->user)
            ->postJson("/api/rubric-categories", $rubric_category_info)
            ->assertJsonValidationErrors(['category']);

    }

    /** @test */
    public function owner_or_grader_can_store_the_rubric()
    {
        $rubric_category_info = [
            'assignment_id' => $this->assignment->id,
            'category' => 'some other category',
            'criteria' => 'some criteria',
            'percent' => 10];

        $this->actingAs($this->user)
            ->postJson("/api/rubric-categories", $rubric_category_info)
            ->assertJson(['type' => 'success']);

        $rubric_category_info = [
            'assignment_id' => $this->assignment->id,
            'category' => 'yet another category',
            'criteria' => 'some criteria',
            'percent' => 10];
        $this->actingAs($this->grader_user)
            ->postJson("/api/rubric-categories", $rubric_category_info)
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function category_percent_criteria_must_be_valid()
    {
        $rubric_category_info = [
            'category' => 'some category',
            'criteria' => 'some criteria',
            'percent' => -3];

        $this->actingAs($this->user)
            ->patchJson("/api/rubric-categories/{$this->rubric_category->id}", $rubric_category_info)
            ->assertJsonValidationErrors(['percent']);


    }

    /** @test */
    public function non_owner_cannot_update_the_rubric()
    {
        $rubric_category_info = [
            'category' => 'some category',
            'criteria' => 'some criteria',
            'percent' => 10];
        $this->actingAs($this->student_user)
            ->patchJson("/api/rubric-categories/{$this->rubric_category->id}", $rubric_category_info)
            ->assertJson(['message' => 'You are not allowed to update this rubric category.']);
    }

    /** @test */
    public function owner_or_grader_can_update_the_rubric()
    {
        $rubric_category_info = [
            'category' => 'some category',
            'criteria' => 'some criteria',
            'percent' => 10];

        $this->actingAs($this->user)
            ->patchJson("/api/rubric-categories/{$this->rubric_category->id}", $rubric_category_info)
            ->assertJson(['type' => 'success']);

        $this->actingAs($this->grader_user)
            ->patchJson("/api/rubric-categories/{$this->rubric_category->id}", $rubric_category_info)
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function non_owner_cannot_delete_the_rubric()
    {
        $this->actingAs($this->student_user)
            ->deleteJson("/api/rubric-categories/{$this->rubric_category->id}")
            ->assertJson(['message' => 'You are not allowed to delete this rubric category.']);
    }

    /** @test */
    public function owner_or_grader_can_delete_the_rubric()
    {
        $this->actingAs($this->grader_user)
            ->deleteJson("/api/rubric-categories/{$this->rubric_category->id}")
            ->assertJson(['message' => 'The rubric category has been deleted.']);
        $this->rubric_category = RubricCategory::create(['assignment_id' => $this->assignment->id,
            'category' => 'some category',
            'criteria' => 'some criteria',
            'percent' => 10,
            'order' => 1]);

        $this->actingAs($this->user)
            ->deleteJson("/api/rubric-categories/{$this->rubric_category->id}")
            ->assertJson(['message' => 'The rubric category has been deleted.']);


    }


    /** @test */
    public function only_student_with_valid_submission_credentials_can_store_rubric_category_submission()
    {
        $student_user_2 = factory(User::class)->create(['role' => 3]);
        $submission = ['rubric_category_id' => $this->rubric_category->id, 'submission' => 'blah blah'];
        $this->actingAs($student_user_2)
            ->patchJson("/api/rubric-category-submissions/{$this->rubric_category->id}/question/{$this->question->id}", ['submission' => $submission])
            ->assertJson(['message' => 'No responses will be saved since you were not assigned to this assignment.']);

        $this->actingAs($this->student_user)
            ->patchJson("/api/rubric-category-submissions/{$this->rubric_category->id}/question/{$this->question->id}", ['submission' => $submission])
            ->assertJson(['message' => 'No responses will be saved since you were not assigned to this assignment.']);

    }

    /** @test */
    public function grader_student_or_instructor_can_get_rubric_category_submission()
    {
        $this->actingAs($this->user)
            ->getJson("/api/rubric-category-submissions/assignment/{$this->assignment->id}/user/{$this->student_user->id}")
            ->assertJson(['type' => 'success']);
        $this->actingAs($this->grader_user)
            ->getJson("/api/rubric-category-submissions/assignment/{$this->assignment->id}/user/{$this->student_user->id}")
            ->assertJson(['type' => 'success']);
        $this->actingAs($this->student_user)
            ->getJson("/api/rubric-category-submissions/assignment/{$this->assignment->id}/user/{$this->student_user->id}")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function non_grader_student_or_instructor_cannot_get_rubric_category_submission()
    {
        $student_user_2 = factory(User::class)->create(['role' => 3]);
        $this->actingAs($student_user_2)
            ->getJson("/api/rubric-category-submissions/assignment/{$this->assignment->id}/user/{$this->student_user->id}")
            ->assertJson(['message' => 'You are not allowed to get these rubric category submissions.']);


    }


}
