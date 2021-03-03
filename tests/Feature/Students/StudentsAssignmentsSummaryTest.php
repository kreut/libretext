<?php

namespace Tests\Feature\Students;

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


class StudentsAssignmentsSummaryTest extends TestCase
{
    use Test;
    use Statistics;

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id, 'students_can_view_weighted_average' => 1]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id, 'show_scores' => 1]);


        $this->student_user_2 = factory(User::class)->create();



        $this->student_user_2->role = 3;

    }

    /** @test */
    public function non_enrolled_student_cannot_get_the_clicker_question()
    {
        $this->actingAs($this->student_user_2)->getJson("/api/assignments/{$this->assignment->id}/clicker-question")
            ->assertJson(['message' => 'You are not allowed to get the clicker questions for this assignment.']);
    }

    /** @test */
    public function enrolled_student_can_get_the_clicker_question()
    {
        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/clicker-question")
            ->assertJson(['type' => 'success']);
    }



}
