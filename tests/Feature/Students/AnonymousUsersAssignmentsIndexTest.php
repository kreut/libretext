<?php

namespace Tests\Feature\Students;


use App\AssignToUser;
use App\FinalGrade;
use App\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use App\Score;
use App\Course;
use App\Assignment;
use App\Enrollment;
use App\SubmissionFile;
use App\Traits\Test;
use App\Traits\Statistics;


class AnonymousUsersAssignmentsIndexTest extends TestCase
{
    use Test;
    use Statistics;

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->anonymous_user = factory(User::class)->create();
        $this->anonymous_user->role = 3;
        $this->anonymous_user->email = 'anonymous';
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id, 'anonymous_users' => 1]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id, 'show_scores' => 1]);

    }

    /** @test */

    public function anonymous_user_can_get_assignments_for_anonymous_course()
    {
        $this->actingAs($this->anonymous_user)->getJson("/api/assignments/courses/{$this->course->id}/anonymous-user")
            ->assertJson(['type' => 'success']);
    }

    /** @test */

    public function anonymous_user_cannot_get_assignments_for_non_anonymous_course()
    {
        $this->anonymous_user->email = 'non_anonymous';
        $this->actingAs($this->anonymous_user)->getJson("/api/assignments/courses/{$this->course->id}/anonymous-user")
            ->assertJson(['message' => 'You are not allowed to view these assignments.']);
    }

}
