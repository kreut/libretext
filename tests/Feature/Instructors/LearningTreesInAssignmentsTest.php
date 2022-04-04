<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Cutup;
use App\Enrollment;
use App\LearningTree;
use App\Question;
use App\Section;
use App\Traits\Test;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LearningTreesInAssignmentsTest extends TestCase
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
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id,
            'solutions_released' => 0,
            'assessment_type' => 'learning tree',
            'submission_count_percent_decrease' => 10,
            'percent_earned_for_exploring_learning_tree' => 50]);
        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id, $this->student_user->id);
        $this->question = factory(Question::class)->create(['page_id' => 1]);


        $this->assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 10
        ]);

        $this->learning_tree_rubric = [
            'learning_tree_success_level' => 'branch',
            'learning_tree_success_criteria' => 'assessment based',
            'min_number_of_successful_assessments' => 1,
            'number_of_successful_branches_for_a_reset' => 1,
            'number_of_resets' => 1,
            'free_pass_for_satisfying_learning_tree_criteria' => 0];
    }
    /** @test */
    public function student_can_get_time_left_in_learning_tree_assignment()
    {
        DB::table('assignment_question_learning_tree')->insert([
            'assignment_question_id' => $this->assignment_question_id,
            'learning_tree_id' => factory(LearningTree::class)->create(['user_id'=>$this->user->id])->id,
            'learning_tree_success_level' => 'branch',
            'learning_tree_success_criteria' => 'time based',
            'min_number_of_successful_assessments' => 1,
            'number_of_successful_branches_for_a_reset' => 1,
            'number_of_resets' => 1,
            'free_pass_for_satisfying_learning_tree_criteria' => 0]);
        $this->actingAs($this->student_user)->patchJson(
            "/api/learning-tree-time-left/get-time-left", [
            'assignment_id' => $this->assignment->id,
            'root_node_question_id' => $this->question->id
        ])->assertJson(['type' => 'success']);
    }

    /** @test */
    public function non_owner_cannot_update_learning_tree_rubric()
    {

        $this->actingAs($this->user_2)->patchJson(
            "/api/assignment-question-learning-tree/assignments/{$this->assignment->id}/question/{$this->question->id}",
            $this->learning_tree_rubric
        )->assertJson(['type' => 'error', 'message' => 'You are not allowed to update that resource.']);


    }

    /** @test */
    public function owner_can_update_learning_tree_rubric()
    {
        $this->actingAs($this->user)->patchJson(
            "/api/assignment-question-learning-tree/assignments/{$this->assignment->id}/question/{$this->question->id}",
            $this->learning_tree_rubric
        )->assertJson(['message' => 'The Learning Tree rubric has been updated.']);

    }

    /** @test */
    public function number_of_successful_resets_is_valid_for_specific_tree()
    {
        $branch_items = [['assessments' => 1], ['assessments' => 1]];
        $this->learning_tree_rubric['number_of_successful_branches_for_a_reset'] = 1;
        $this->learning_tree_rubric['number_of_resets'] = 3;
        $this->learning_tree_rubric['branch_items'] = $branch_items;
        $this->actingAs($this->user)->patchJson(
            "/api/assignment-question-learning-tree/assignments/{$this->assignment->id}/question/{$this->question->id}",
            $this->learning_tree_rubric
        )->assertJson(['errors' => ['number_of_resets' => ['Students must complete 1 branch and are allowed 3 resets.  But there are only 2 total branches in the Learning Tree.']]]);
    }

    /** @test */
    public function number_of_successful_branches_is_valid_for_specific_tree()
    {
        $branch_items = [['assessments' => 1], ['assessments' => 1]];
        $this->learning_tree_rubric['number_of_successful_branches_for_a_reset'] = 4;
        $this->learning_tree_rubric['number_of_resets'] = 1;
        $this->learning_tree_rubric['branch_items'] = $branch_items;
        $this->actingAs($this->user)->patchJson(
            "/api/assignment-question-learning-tree/assignments/{$this->assignment->id}/question/{$this->question->id}",
            $this->learning_tree_rubric
        )->assertJson(['errors' => ['number_of_successful_branches_for_a_reset' => ['The Learning Tree only has 2 branches but students need to successfully complete a minimum of 4 branches before they can resubmit.']]]);

    }

    /** @test */
    public function min_number_of_successful_assessments_is_valid_for_specific_tree()
    {
        $branch_items = [['assessments' => 1, 'description' => 'First Branch'], ['assessments' => 1, 'description' => 'Second Branch']];
        $this->learning_tree_rubric['min_number_of_successful_assessments'] = 8;
        $this->learning_tree_rubric['number_of_successful_branches_for_a_reset'] = 1;
        $this->learning_tree_rubric['number_of_resets'] = 1;
        $this->learning_tree_rubric['branch_items'] = $branch_items;
        $this->actingAs($this->user)->patchJson(
            "/api/assignment-question-learning-tree/assignments/{$this->assignment->id}/question/{$this->question->id}",
            $this->learning_tree_rubric
        )->assertJson(['errors' => ['min_number_of_successful_assessments' => ['First Branch only has 1 assessment and you require that your students complete 8 assessments within that branch.']]]);

    }

    /** @test */
    public function non_student_cannot_get_assignment_question_learning_tree_info()
    {
        $this->actingAs($this->user_2)->getJson(
            "/api/assignment-question-learning-tree/assignments/{$this->assignment->id}/question/{$this->question->id}/info")
            ->assertJson(['message' => 'You are not allowed to access this assignment.']);


    }

    /** @test */
    public function non_student_cannot_get_time_left_in_learning_tree_assignment()
    {
        $this->actingAs($this->user_2)->patchJson(
            "/api/learning-tree-time-left/get-time-left", [
            'assignment_id' => $this->assignment->id,
            'root_node_question_id' => $this->question->id
        ])->assertJson(['message' => 'You are not a student in this course.']);
    }


    /** @test */
    public function student_cannot_get_time_left_in_learning_tree_assignment_if_question_not_in_assignment()
    {
        $this->actingAs($this->student_user)->patchJson(
            "/api/learning-tree-time-left/get-time-left", [
            'assignment_id' => $this->assignment->id,
            'root_node_question_id' => 1222323 //bogus id
        ])->assertJson(['message' => 'That is not a question in the assignment.']);
    }



}
