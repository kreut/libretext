<?php

namespace Tests\Feature;

use App\Assignment;
use App\AssignToTiming;
use App\AutoRelease;
use App\Course;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutoReleaseTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $user->id]);
        $this->assignment = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'shown' => 0,
            'show_scores' => 0,
            'solutions_released' => 0,
            'students_can_view_assignment_statistics' => 0]);
    }

    /** @test */
    public function auto_release_for_course_cannot_be_updated_by_non_owner()
    {
        $user_2 = factory(User::class)->create();
        $this->actingAs($user_2)
            ->patchJson("/api/courses/auto-release/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to set the auto-release for this course.']);
    }

    /** @test */
    public function compare_assignment_to_course_default_can_only_be_done_by_instructor()
    {
        $user_2 = factory(User::class)->create(['role'=>3]);
        $this->actingAs($user_2)
            ->getJson("/api/auto-release/compare-to-default/assignment/{$this->assignment->id}/course/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to compare the assignment auto-release to the default course auto-release.']);

    }

    /** @test */
    public function correctly_updates_shown_when_past_earliest_assign_to()
    {
        //show 30 minutes before it becomes available.
        //Now it's 1pm.  Available at 1:20pm
        $autoRelease = new AutoRelease();
        $autoRelease->shown = '30 minutes';
        $autoRelease->type = 'assignment';
        $autoRelease->type_id = $this->assignment->id;
        $autoRelease->save();


        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $this->assignment->id;
        $assignToTiming->available_from = Carbon::now()->add('20 minutes');
        $assignToTiming->due = Carbon::now()->addHours();
        $assignToTiming->save();

        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $this->assignment->id;
        $assignToTiming->available_from = Carbon::now()->add('10 minutes');
        $assignToTiming->due = Carbon::now()->addHours();
        $assignToTiming->save();
        $this->artisan('process:autoRelease');
        $this->assertDatabaseHas('assignments', ['shown' => 1]);

    }

    /** @test */
    public function does_not_update_shown_when_not_past_earliest_assign_to()
    {
        //show 30 minutes before it becomes available.
        //Now it's 1pm.  Available at 2pm

        $autoRelease = new AutoRelease();
        $autoRelease->shown = '30 minutes';
        $autoRelease->type = 'assignment';
        $autoRelease->type_id = $this->assignment->id;
        $autoRelease->save();

        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $this->assignment->id;
        $assignToTiming->available_from = Carbon::now()->addHours();
        $assignToTiming->due = Carbon::now()->addHours();
        $assignToTiming->save();

        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $this->assignment->id;
        $assignToTiming->available_from = Carbon::now()->addHours();
        $assignToTiming->due = Carbon::now()->addHours();
        $assignToTiming->save();
        $this->artisan('process:autoRelease');
        $this->assertDatabaseHas('assignments', ['shown' => 0]);


    }

    /** @test */
    public function correctly_updates_post_releases_when_past_latest_due()
    {
        //release solutions 30 minutes after last due
        //Now it's 1pm.  Latest due 12pm.  Release solutions at 12:30pm


        $autoRelease = new AutoRelease();
        $autoRelease->show_scores = '30 minutes';
        $autoRelease->solutions_released = '30 minutes';
        $autoRelease->students_can_view_assignment_statistics = '30 minutes';
        $autoRelease->type = 'assignment';
        $autoRelease->type_id = $this->assignment->id;
        $autoRelease->save();


        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $this->assignment->id;
        $assignToTiming->available_from = Carbon::now()->subHours();
        $assignToTiming->due = Carbon::now()->subHours();
        $assignToTiming->save();

        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $this->assignment->id;
        $assignToTiming->available_from = Carbon::now()->subHours();
        $assignToTiming->due = Carbon::now()->subHours();
        $assignToTiming->save();
        $this->artisan('process:autoRelease');
        $this->assertDatabaseHas('assignments', ['show_scores' => 1,
            'solutions_released' => 1,
            'students_can_view_assignment_statistics' => 1]);

    }

    /** @test */
    public function correctly_does_not_update_if_final_submission_deadline_is_chosen_but_not_past()
    {
        //release solutions 30 minutes after last final submission deadline
        //Now it's 1pm.  Latest final submission deadline 12pm.  Release solutions at 12:30pm

        $autoRelease = new AutoRelease();
        $autoRelease->show_scores = '30 minutes';
        $autoRelease->solutions_released = '30 minutes';
        $autoRelease->students_can_view_assignment_statistics = '30 minutes';
        $autoRelease->students_can_view_assignment_statistics_after = 'final submission deadline';
        $autoRelease->type = 'assignment';
        $autoRelease->type_id = $this->assignment->id;
        $autoRelease->save();


        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $this->assignment->id;
        $assignToTiming->available_from = Carbon::now()->subHours();
        $assignToTiming->due = Carbon::now()->subHours();
        $assignToTiming->save();

        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $this->assignment->id;
        $assignToTiming->available_from = Carbon::now()->subHours();
        $assignToTiming->due = Carbon::now()->subHours();
        $assignToTiming->final_submission_deadline = Carbon::now()->addHours();
        $assignToTiming->save();
        $this->artisan('process:autoRelease');
        $this->assertDatabaseHas('assignments', ['show_scores' => 1,
            'solutions_released' => 1,
            'students_can_view_assignment_statistics' => 0]);//not this since it's going by the final submission deadline

    }

    /** @test */
    public function does_not_update_post_releases_when_not_past_latest_due()
    {
        //release solutions 30 minutes after last due
        //Now it's 1pm.  Latest due 2pm.  Release solutions at 2:30pm


        $autoRelease = new AutoRelease();
        $autoRelease->show_scores = '30 minutes';
        $autoRelease->solutions_released = '30 minutes';
        $autoRelease->students_can_view_assignment_statistics = '30 minutes';
        $autoRelease->type = 'assignment';
        $autoRelease->type_id = $this->assignment->id;
        $autoRelease->save();


        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $this->assignment->id;
        $assignToTiming->available_from = Carbon::now()->subHours();
        $assignToTiming->due = Carbon::now()->addHour();
        $assignToTiming->save();

        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $this->assignment->id;
        $assignToTiming->available_from = Carbon::now()->subHours();
        $assignToTiming->due = Carbon::now()->addHour();
        $assignToTiming->save();
        $this->artisan('process:autoRelease');
        $this->assertDatabaseHas('assignments', ['show_scores' => 0,
            'solutions_released' => 0,
            'students_can_view_assignment_statistics' => 0]);


    }


}
