<?php

namespace Tests\Feature\Instructors;

use App\Question;
use App\Score;
use App\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\User;
use App\Course;
use App\Assignment;
use App\Enrollment;
use App\SubmissionFile;

class GradingTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();

        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

        //create a student and enroll in the class
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;

        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);

        $this->assignment_file = factory(SubmissionFile::class)->create(['type' => 'a', 'user_id' => $this->student_user->id, 'assignment_id' => $this->assignment->id]);

        $this->question = factory(Question::class)->create(['page_id' => 1]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'can_view' =>1,
            'can_submit' => 1,
            'clicker_results_released' =>0,
            'open_ended_submission_type' => 'file',
            'points' => 10
        ]);
        $this->question_file = factory(SubmissionFile::class)->create([
            'type' => 'q',
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id
        ]);


    }
    /** @test */
    public function cannot_get_assignment_files_if_not_owner()
    {
        $this->actingAs($this->user_2)->getJson("/api/submission-files/{$this->assignment->id}/all_students")
            ->assertJson([
                'type' => 'error',
                'message' => 'You are not allowed to access these submissions for grading.'
            ]);

    }

    /** @test */
    public function assignments_of_scoring_type_p_and_submission_files_at_the_question_level_will_use_min_of_the_points_per_question_compared_to_the_sum_of_the_question_and_file_points()
    {



        $question_score = 5;

        $file_submission_score = 2.0;

        Submission::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id'=> $this->question->id,
            'submission' => 'some other submission',
            'score' => $question_score,
            'answered_correctly_at_least_once' => 0,
            'submission_count' => 1]);


        //Now submit a question_file score
        $this->actingAs($this->user)->postJson("/api/submission-files/score", [
            'type' => 'question',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student_user->id,
            'score' =>  $file_submission_score])
            ->assertJson(['type'=> 'success']);


        $score = DB::table('scores')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->first();




        $this->assertEquals( (float) $score->score,$question_score + $file_submission_score);
    }




    /** @test */
    public function assignments_of_scoring_type_p_and_submission_files_at_the_question_level_cannot_submit_a_score_greater_than_the_total_number_of_points_in_the_question()
    {


        $question_score = 5;

        $file_submission_score = 30;
         DB::table('assignment_question')
            ->where('question_id', $this->question->id)
            ->where('assignment_id', $this->assignment->id)
            ->first();

        Submission::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id'=> $this->question->id,
            'submission' => 'some other submission',
            'score' => $question_score,
            'answered_correctly_at_least_once' => 0,
            'submission_count' => 1]);


        //Now submit a question_file score
        $this->actingAs($this->user)->postJson("/api/submission-files/score", [
            'type' => 'question',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student_user->id,
            'score' =>  $file_submission_score])
        ->assertJson(['message'=>  'The total of your Question Submission Score and File Submission score can\'t be greater than the total number of points for this question.']);
    }




    /** @test */
    public function owner_can_submit_score()
    {


    }

    /** @test */

    public function non_owner_can_not_submit_score()
    {


    }

    /** @test */

    public function score_must_be_valid()
    {


    }



    /** @test */

    public function can_get_assignment_files_if_owner()
    {

        $this->actingAs($this->user)->getJson("/api/submission-files/{$this->assignment->id}/all_students")
            ->assertJson(['type' => 'success']);

    }




    /** @test */
    public function can_download_assignment_file_if_owner()
    {
        $this->markTestIncomplete(
            'Not sure how to test'
        );

    }
/** @test */

    public function can_download_assignment_file_if_grader(){


    }
    /** @test */
    public function cannot_download_assignment_file_if_not_owner()
    {
  /*$this->actingAs($this->user_2)->postJson("/api/submission-files/download",
            [
                'assignment_id' => $this->assignment->id,
                'submission' => $this->assignment_file->submission
            ]
        );*/
     //NEED EXCEPTION
            //->assertJson(['type' => 'error', 'message' => 'You are not allowed to download that assignment file.']);

    }

    /** @test */
    public function can_get_temporary_url_from_request_if_owner()
    {
        $this->actingAs($this->user)->postJson("/api/submission-files/get-temporary-url-from-request",
            [
                'assignment_id' => $this->assignment->id,
                'file' => $this->assignment_file->submission
            ]
        )
            ->assertJson(['type' => 'success']);

    }


    /** @test */
    public function cannot_get_temporary_url_if_not_owner()
    {
        $this->actingAs($this->user_2)->postJson("/api/submission-files/get-temporary-url-from-request",
            [
                'assignment_id' => $this->assignment->id,
                'file' => $this->assignment_file->submission
            ]
        )
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to create a temporary URL.']);

    }

    /** @test */
    public function can_store_text_feedback_if_owner()
    {
        $this->actingAs($this->user)->postJson("/api/submission-files/text-feedback",
            [
                'assignment_id' => $this->assignment->id,
                'user_id' => $this->student_user->id,
                'type' => 'assignment',
                'textFeedback' => 'Some text feedback'
            ]
        )
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_store_text_feedback_if_not_owner()
    {
        $this->actingAs($this->user_2)->postJson("/api/submission-files/text-feedback",
            [
                'assignment_id' => $this->assignment->id,
                'user_id' => $this->student_user->id,
                'textFeedback' => 'Some text feedback'
            ]
        )
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to submit comments for this assignment.']);

    }


}
