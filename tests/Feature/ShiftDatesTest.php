<?php

namespace Tests\Feature;

use App\Assignment;
use App\AssignToTiming;
use App\Course;
use App\Section;
use App\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use App\Traits\Test;

class ShiftDatesTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_1 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'shown' => 0,
            'show_scores' => 0,
            'solutions_released' => 0,
            'students_can_view_assignment_statistics' => 0]);
    }

    /** @test */
    public function non_owner_cannot_get_dates()
    {
        $this->actingAs($this->user_1)
            ->getJson("/api/assignments/{$this->course->id}/dates")
            ->assertJson(['message' => "You are not allowed to get the dates since you do not own this course."]);

    }

    /** @test */
    public function non_owner_cannot_shift_dates()
    {
        $this->actingAs($this->user_1)
            ->postJson("/api/assignments/{$this->course->id}/shift-dates", ['assignment_ids' => [$this->assignment->id]])
            ->assertJson(['message' => "You are not allowed to shift the dates since you do not own all of these assignments."]);
    }

    /** @test */
    public function shift_by_must_be_valid()
    {
        $this->actingAs($this->user)
            ->postJson("/api/assignments/{$this->course->id}/shift-dates",
                ['shift_by' => 'ooga',
                    'assignment_ids' => [$this->assignment->id]
                ])
            ->assertJsonValidationErrors('shift_by');
    }

    /** @test */
    public function dates_are_correctly_shifted()
    {
        $student_user = factory(User::class)->create(['role' => 3]);
        $section = factory(Section::class)->create(['course_id' => $this->course->id]);
        DB::table('enrollments')->insert([
            'user_id' => $student_user->id,
            'course_id' => $this->course->id,
            'section_id' => $section->id]);
        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id, $student_user->id);
        $original_assign_to_timing = AssignToTiming::where('assignment_id', $this->assignment->id)->first();
        $shift_by = '1 day';
        $this->actingAs($this->user)
            ->postJson("/api/assignments/{$this->course->id}/shift-dates",
                ['shift_by' => '1 day',
                    'assignment_ids' => [$this->assignment->id]
                ])
            ->assertJson(['type' => 'success']);
        $new_assign_to_timing = AssignToTiming::where('assignment_id', $this->assignment->id)->first();

        foreach (['available_from', 'due', 'final_submission_deadline'] as $key) {
            if ($original_assign_to_timing->{$key}) {
                $date = CarbonImmutable::parse($original_assign_to_timing->{$key});
                $this->assertEquals($date->add($shift_by)->toDateTimeString(), $new_assign_to_timing->{$key});
            }
        }
    }
}
