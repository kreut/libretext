<?php

namespace Tests\Feature;

use App\Assignment;
use App\AssignToTiming;
use App\AssignToUser;
use App\Course;
use App\Enrollment;
use App\FinalGrade;
use App\Grader;
use App\Question;
use App\Section;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReviewHistoryTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;

        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);


        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->question = factory(Question::class)->create(['page_id' => 12398713]);
        //enroll a student in that course
        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $this->assignment->id;
        $assignToTiming->available_from = Carbon::now()->subDays(3);
        $assignToTiming->due = Carbon::now()->subDays(2);
        $assignToTiming->save();

        $assignToUser = new AssignToUser();
        $assignToUser->assign_to_timing_id = $assignToTiming->id;
        $assignToUser->user_id = $this->student_user->id;

        $assignToUser->save();
    }

    /** @test */
    public function assignment_must_be_past_due()
    {
        $assignToTiming = AssignToTiming::where('assignment_id', $this->assignment->id)->first();
        $assignToTiming->due = Carbon::now()->addDays(2);
        $assignToTiming->save();

        $this->actingAs($this->student_user)->patchJson("/api/review-history/assignment/{$this->assignment->id}/question/{$this->question->id}", ['reviewSessionId' => 123]
        )
            ->assertJson(['message' => 'unauthorized']);
    }


    /** @test */
    public function review_session_id_must_be_present()
    {

        $this->actingAs($this->student_user)->patchJson("/api/review-history/assignment/{$this->assignment->id}/question/{$this->question->id}")
            ->assertJson(['message' => 'No review session ID is present.']);
    }


}
