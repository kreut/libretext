<?php

namespace Tests\Feature;

use App\Assignment;
use App\AssignToTiming;
use App\AssignToUser;
use App\BetaAssignment;
use App\BetaCourse;
use App\Course;
use App\LearningTree;
use App\Question;
use App\School;
use App\Section;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubmitWorkTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->question = factory(Question::class)->create(['page_id' => 3123123]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => '0'
        ]);

    }


    /** @test */
    public function work_cannot_be_submitted_if_assignment_is_past_due()
    {
     $this->_enrollStudentUser();
        $this->actingAs($this->student_user)
            ->patchJson("/api/submitted-work/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['message' => 'You cannot submit work since this assignment is past due.']);

    }

    /** @test */
    public function unenrolled_student_cannot_submit_work()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/submitted-work/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['message' => 'You cannot submit work since you are not enrolled in this course.']);

    }


    /** @test */
    public function unenrolled_student_cannot_submit_audio_work()
    {
        $this->actingAs($this->student_user)
            ->postJson("/api/submitted-work/assignments/{$this->assignment->id}/questions/{$this->question->id}/audio")
            ->assertJson(['message' => 'You cannot submit work since you are not enrolled in this course.']);

    }

    /** @test */
    public function cannot_delete_submitted_work_if_assignment_is_closed()
    {
        $this->_enrollStudentUser();
        $this->actingAs($this->student_user)
            ->deleteJson("/api/submitted-work/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['message' => 'You cannot submit work since this assignment is past due.']);
    }


    /** @test */
    public function non_owner_cannot_get_submitted_work_with_pending_score()
    {
        $new_user = factory(User::class)->create();
        $this->actingAs($new_user)
            ->getJson("/api/submitted-work/assignments/{$this->assignment->id}/questions/{$this->question->id}/user/{$this->student_user->id}/with-pending-score")
            ->assertJson(['message' => 'You are not allowed to get the submitted work for this question.']);
    }

    private function _enrollStudentUser() {
        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $this->assignment->id;
        $assignToTiming->available_from = Carbon::now()->addHours();
        $assignToTiming->due = Carbon::now()->subHour();
        $assignToTiming->save();
        $assignToUser = new AssignToUser();
        $assignToUser->assign_to_timing_id = $assignToTiming->id;
        $assignToUser->user_id = $this->student_user->id;
        $assignToUser->save();
        DB::table('enrollments')->insert(['user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id]);
    }

}
