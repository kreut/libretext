<?php

namespace Tests\Feature\Instructors;

use App\AssignmentGroupWeight;
use App\Course;
use App\Grader;
use App\Section;
use App\User;
use App\Assignment;
use App\Extension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Traits\Test;

class AutoGradedSubmissionsTest extends TestCase
{

    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->user_2 = factory(User::class)->create();
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->assignment_2 = factory(Assignment::class)->create(['course_id' => $this->course->id]);
    }


    /** @test */

    public function non_owner_cannot_get_auto_graded_submissions()
    {

        $this->actingAs($this->user_2)
            ->getJson("/api/auto-graded-submissions/{$this->assignment->id}/get-auto-graded-submissions-by-assignment")
            ->assertJson(['message' => "You can't get the auto-graded submissions for an assignment that is not in one of your courses."]);

    }
    /** @test */

    public function owner_cannot_get_auto_graded_submissions()
    {

        $this->actingAs($this->user)
            ->getJson("/api/auto-graded-submissions/{$this->assignment->id}/get-auto-graded-submissions-by-assignment")
            ->assertJson(['type' => 'success']);

    }
}
