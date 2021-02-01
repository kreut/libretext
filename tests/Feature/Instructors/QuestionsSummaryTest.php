<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Extension;
use App\Cutup;
use App\Solution;
use App\User;
use App\Question;
use App\SubmissionFile;
use Carbon\Carbon;
use App\Score;
use App\Submission;
use App\Traits\Statistics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionsSummaryTest extends TestCase
{
    use Statistics;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $course->id, 'solutions_released' => 0]);
        $this->question = factory(Question::class)->create(['page_id' => 1]);
        $this->question_2 = factory(Question::class)->create(['page_id' => 2]);
        $this->question_points = 10;
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => $this->question_points,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => $this->question_points,
            'order' => 2,
            'open_ended_submission_type' => 'file'
        ]);


    }

    /** @test */

    public function non_owner_cannot_reorder_an_assignment()
    {
        $this->actingAs($this->user_2)->patchJson("/api/assignments/{$this->assignment->id}/questions/order", [
            'ordered_questions' => [$this->question_2->id, $this->question->id]
        ])->assertJson(['message' => 'You are not allowed to order the questions for this assignment.']);

    }

    /** @test */

    public function owner_can_reorder_an_assignment()
    {

        $this->actingAs($this->user)->patchJson("/api/assignments/{$this->assignment->id}/questions/order", [
            'ordered_questions' => [$this->question_2->id, $this->question->id]
        ]);
        $assignment_questions = DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->orderBy('order')
            ->select('question_id')
            ->get()
            ->pluck('question_id')
            ->toArray();

        $this->assertEquals([$this->question_2->id, $this->question->id], $assignment_questions);

    }


}
