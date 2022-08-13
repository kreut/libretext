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
        $this->question = factory(Question::class)->create(['page_id'=>17652]);
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
    public function an_assignment_with_a_learning_tree_assessment_will_return_this_info()
    {

        //create a student and enroll in the class
        DB::table('assignment_question_learning_tree')->insertGetId([
            'assignment_question_id' => $this->assignment_question_id,
            'learning_tree_id' => $this->learning_tree->id,
            'learning_tree_success_level' => 'tree',
            'learning_tree_success_criteria' => 'time based',
            'free_pass_for_satisfying_learning_tree_criteria' => 0
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
