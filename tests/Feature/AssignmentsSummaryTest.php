<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Section;
use App\Traits\Test;
use App\User;
use Tests\TestCase;
use function factory;

class AssignmentsSummaryTest extends TestCase
{
    use Test;

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
        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id, $this->student_user->id);

        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;

    }

    /** @test * */
    public function user_can_get_summary_info_if_enrolled_in_course()
    {

        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/summary")
            ->assertJson(['type' => 'success']);

    }

    /** @test * */
    public function student_cannot_get_scores_info_if_students_can_view_assignment_statistics_is_false()
    {

        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/scores-info")
            ->assertJson(['type' => 'error',
                'message' => "You are not allowed to get these scores."]);

    }

    /** @test * */
    public function student_can_get_scores_info_if_students_can_view_assignment_statistics_is_true()
    {
        $this->assignment->students_can_view_assignment_statistics = 1;
        $this->assignment->save();
        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/scores-info")
            ->assertJson(['type' => 'success']);

    }

    /** @test * */
    public function user_cannot_get_summary_info_if_not_enrolled_in_course()
    {

        $this->actingAs($this->student_user_2)->getJson("/api/assignments/{$this->assignment->id}/summary")
            ->assertJson(['type' => 'error',
                'message' => "You are not allowed to retrieve this summary."]);

    }


    /** @test * */
    public function owner_can_get_summary_info_if_not_enrolled_in_course()
    {
        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/summary")
            ->assertJson(['type' => 'success']);

    }

}
