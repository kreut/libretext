<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\FinalGrade;
use App\User;
use App\ExtraCredit;
use App\Extension;
use App\Traits\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AssignmentGradebookByQuestionAndStudentTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

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
