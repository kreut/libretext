<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Question;
use App\User;
use App\Traits\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssignmentGradebookByQuestionAndStudentTest extends TestCase
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
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);


    }

    /** @test */
    public function correctly_computes_the_percent_correct_by_student()
    {
        $this->question = factory(Question::class)->create(['page_id' => 1]);
        $this->question_2 = factory(Question::class)->create(['page_id' => 2]);
        $this->question_points = 10;
        $this->question_points_2 = 20;
        $score_1 = 5;
        $score_2 = 1;
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => $this->question_points
        ]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => $this->question_points_2
        ]);;

        DB::table('submissions')->insert([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission' => 'some_submission',
            'submission_count' => 1,
            'answered_correctly_at_least_once' => 0,
            'score' => $score_1
        ]);
        DB::table('submissions')->insert([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'submission' => 'some_submission',
            'submission_count' => 1,
            'answered_correctly_at_least_once' => 0,
            'score' => $score_2
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/scores/assignment/{$this->assignment->id}/get-assignment-questions-scores-by-user");
        $this->assertEquals($response['rows'][0]['percent_correct'], 100* ($score_1 + $score_2) / ($this->question_points + $this->question_points_2) . '%');


    }

    /** @test */

    public function non_owner_cannot_get_the_assignment_scores_by_question_and_user()
    {


        $this->actingAs($this->user_2)->getJson("/api/scores/assignment/{$this->assignment->id}/get-assignment-questions-scores-by-user")
            ->assertJson(['message' => 'You are not allowed to retrieve the question scores by user for this assignment.']);
    }

    /** @test */

    public function owner_can_get_the_assignment_scores_by_question_and_user()
    {
        $this->actingAs($this->user)->getJson("/api/scores/assignment/{$this->assignment->id}/get-assignment-questions-scores-by-user")
            ->assertJson(['type' => 'success']);
    }


}
