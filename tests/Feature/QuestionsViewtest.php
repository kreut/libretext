<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Extension;
use App\User;
use App\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionsViewTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create();
        $this->assignment = factory(Assignment::class)->create();
        factory(Question::class)->create();

        $this->assignment->questions()->attach(Question::find(1));

        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;

        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);

    }
    /** @test */
    public function can_submit_response()
    {

        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> 1,
            'submission' => 'some submission'])
            ->assertJson(['type' => 'success']);

    }
/** @test */
    public function can_update_response()
    {

        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> 1,
            'submission' => 'some submission']);


        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> 1,
            'submission' => 'some other submission'])
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_submit_response_if_question_not_in_assignment()
    {
        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> 0,
            'submission' => 'some submission'])
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since that question is not in the assignment.']);

    }
/** @test */
    public function cannot_submit_response_if_user_not_enrolled_in_course()
    {
        $this->actingAs($this->student_user_2)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> 1,
            'submission' => 'some submission'])
            ->assertJson(['type' => 'error',
                'message'=> 'No responses will be saved since the assignment is not part of your course.']);

    }
    /** @test */
    public function can_submit_response_if_assignment_past_due_has_extension()
    {
        $this->assignment->due = "2001-03-05 09:00:00";
        $this->assignment->save();

        Extension::create(['user_id' => $this->student_user->id,
            'assignment_id'=> $this->assignment->id,
            'extension'=>'2027-01-01 09:00:00']);

        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> 1,
            'submission' => 'some submission'])
            ->assertJson(['type' => 'success']);

    }

/** @test */
    public function cannot_submit_response_if_assignment_past_due_and_no_extension()
    {
        $this->assignment->due = "2001-03-05 09:00:00";
        $this->assignment->save();

        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> 1,
            'submission' => 'some submission'])
            ->assertJson(['type' => 'error', 'message' => 'No responses will be saved since the due date for this assignment has passed.']);

    }

    /** @test  */
    public function cannot_submit_response_if_assignment_past_due_and_past_extension()
    {
        $this->assignment->due = "2001-03-05 09:00:00";
        $this->assignment->save();

        Extension::create(['user_id' => $this->student_user->id,
            'assignment_id'=> $this->assignment->id,
            'extension'=>'2020-01-01 09:00:00']);

        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> 1,
            'submission' => 'some submission'])
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since your extension for this assignment has passed.']);

    }
/** @test */
    public function cannot_submit_response_if_assignment_not_yet_available()
    {
        $this->assignment->available_from = "2035-03-05 09:00:00";
        $this->assignment->save();


        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> 1,
            'submission' => 'some submission'])
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since this assignment is not yet available.']);

    }


    public function can_get_titles_of_learning_tree()
    {


    }

    public function can_get_assignment_title_if_student_in_course() {

    }

    public function cannot_get_assignment_title_if_not_student_in_course() {

    }
    public function can_get_assignment_questions_if_student_in_course(){
//do I check for extensions?

    }

    public function cannot_get_assignment_questions_if_not_student_in_course(){


    }

    public function can_remove_question_from_assignment_if_owner() {

    }

    public function cannot_remove_question_from_assignment_if_not_owner() {

    }

}
