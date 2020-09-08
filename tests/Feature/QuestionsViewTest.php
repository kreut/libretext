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
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->question = factory(Question::class)->create(['page_id'=>1]);
        $this->question_2 = factory(Question::class)->create(['page_id'=>2]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id'=> $this->question->id,
            'points'=> 10
        ]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id'=> $this->question_2->id,
            'points'=> 10
        ]);;

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

    public function assignments_of_scoring_type_c_will_count_the_number_of_submissions_and_compare_to_the_number_of_questions(){
    $this->assignment->scoring_type = 'c';
    $this->assignment->save();

    $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> $this->question->id,
            'submission' => 'some submission']);

    $score = DB::table('scores')->where('user_id', $this->student_user->id)
                            ->where('assignment_id', $this->assignment->id)
                            ->first();
    $this->assertEquals(null, $score, 'No assignment score saved in not completed assignment.');



        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> $this->question_2->id,
            'submission' => 'some other submission'])
            ->assertJson(['type' => 'success']);

        $score = DB::table('scores')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->get()
            ->pluck('score');
        $this->assertEquals('C', $score[0], 'Assignment marked as completed when all questions are answered.');

    }
    /** @test */

    public function assignments_of_scoring_type_s_and_no_question_files_will_compute_the_score_based_on_the_question_points(){
        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> $this->question->id,
            'submission' => 'some submission']);

        $score = DB::table('scores')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->get()
            ->pluck('score');


       $this->assertEquals($this->assignment->default_points_per_question, $score[0], 'Score saved when student submits.');

       //do it again and it should update

        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> $this->question->id,
            'submission' => 'some submission']);

        $score = DB::table('scores')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->get()
            ->pluck('score');

        $this->assertEquals(2*$this->assignment->default_points_per_question, $score[0], 'Score saved when student submits.');


    }

    /** @test */




    /** @test */

    public function cannot_store_a_question_file_if_it_is_not_in_the_assignment() {


    }

    /** @test */

    public function cannot_store_a_question_file_if_it_has_the_wrong_type() {
//testing for question/assignment

    }

    /** @test */

    public function cannot_store_a_question_file() {


    }

    /** @test */

    public function can_toggle_question_files_if_you_are_the_owner() {


    }

    /** @test */

    public function cannot_toggle_question_files_if_you_are_not_the_owner() {


    }
    /** @test */
    public function can_submit_response()
    {

        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> $this->question->id,
            'submission' => 'some submission'])
            ->assertJson(['type' => 'success']);

    }
/** @test */
    public function can_update_response()
    {

        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> $this->question->id,
            'submission' => 'some submission']);


        $this->actingAs($this->student_user)->postJson("/api/submissions",[
            'assignment_id' => $this->assignment->id,
            'question_id'=> $this->question->id,
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
            'question_id'=> $this->question->id,
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
            'question_id'=> $this->question->id,
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
            'question_id'=> $this->question->id,
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
            'question_id'=> $this->question->id,
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
            'question_id'=> $this->question->id,
            'submission' => 'some submission'])
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since this assignment is not yet available.']);

    }

/** @test */
    public function can_get_titles_of_learning_tree()
    {
        $this->actingAs($this->user)->getJson("/api/libreverse/library/chem/page/21691/title")
            ->assertSeeText('Studying Chemistry');


    }
/** @test  */
    public function can_get_assignment_title_if_owner_course() {
        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}")
            ->assertJson(['name' => $this->assignment->name]);
    }

    /** @test  */
    public function can_get_assignment_title_if_student_in_course() {
        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}")
            ->assertJson(['name' => $this->assignment->name]);
    }
/** @test */
    public function cannot_get_assignment_title_if_not_student_in_course() {
        $this->actingAs($this->student_user_2)->getJson("/api/assignments/{$this->assignment->id}")
            ->assertJson([ 'type' => 'error',
                'message' => 'You are not allowed to access this assignment.']);
    }
/** @test  */
    public function can_get_assignment_questions_if_student_in_course(){
        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/questions/view")
            ->assertJson([ 'type' => 'success']);

    }
    /** @test  */
    public function cannot_get_assignment_questions_if_not_student_in_course(){
        $this->actingAs($this->student_user_2)->getJson("/api/assignments/{$this->assignment->id}/questions/view")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to access this assignment.']);

    }
/** @test */
    public function can_remove_question_from_assignment_if_owner() {
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'success']);

    }
/** @test */
    public function cannot_remove_question_from_assignment_if_not_owner() {
        $this->actingAs($this->user_2)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to remove this question from this assignment.']);
    }

}
