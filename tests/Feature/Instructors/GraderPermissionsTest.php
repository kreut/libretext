<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Grader;
use App\Question;
use App\Section;
use App\User;
use App\Traits\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GraderPermissionsTest extends TestCase
{
    use Test;

    private $course;
    private $user_2;
    private $assignment;
    private $grader_user;
    private $user;
    private $grader_user_2;
    private $section;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();

        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);

        $this->grader_user = factory(User::class)->create();
        $this->grader_user->role = 4;
        Grader::create(['user_id' => $this->grader_user->id, 'section_id' => $this->section->id]);
        $this->grader_user_2 = factory(User::class)->create();
        $this->grader_user_2->role = 4;
    }
    /** @test */
    public function non_owner_cannot_update_single_grader_permissions()
    {
        $this->actingAs($this->user_2)->patchJson("/api/grader-permissions/{$this->assignment->id}/{$this->grader_user->id}/1")
            ->assertJson(['message' => 'You are not allowed to give this grader access to this assignment.']);
    }

    /** @test */
    public function must_be_grader_in_course_to_update_single_grader_permissions()
    {
        $this->actingAs($this->user)->patchJson("/api/grader-permissions/{$this->assignment->id}/{$this->grader_user_2->id}/1")
            ->assertJson(['message' => 'You are not allowed to give this grader access to this assignment.']);
    }

    /** @test */
    public function owner_can_update_single_grader_permissions()
    {
        $this->actingAs($this->user)->patchJson("/api/grader-permissions/{$this->assignment->id}/{$this->grader_user->id}/1")
            ->assertJson(['type' => 'info']);
    }

    /** @test */
    public function owner_can_update_assignment_level_grader_permissions()
    {
        $this->actingAs($this->user)->patchJson("/api/grader-permissions/assignment/{$this->assignment->id}/1")
            ->assertJson(['type' => 'success']);

        $this->assertEquals($this->assignment->graders[0]->id, $this->grader_user->id);

    }

    /** @test */
    public function owner_can_update_course_level_grader_permissions()
    {
        $this->actingAs($this->user)->patchJson("/api/grader-permissions/course/{$this->course->id}/1")
            ->assertJson(['type' => 'success']);

        $this->assertEquals($this->assignment->graders[0]->id, $this->grader_user->id);

    }

    /** @test */
    public function nonowner_cannot_update_assignment_level_grader_permissions()
    {

        $this->actingAs($this->user_2)->patchJson("/api/grader-permissions/assignment/{$this->assignment->id}/1")
            ->assertJson(['message' => 'You are not allowed to give graders access to this assignment.']);


    }


    /** @test */

    public function non_owner_cannot_get_grader_permissions()
    {

        $this->actingAs($this->user_2)->getJson("/api/grader-permissions/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to get the grader permissions for this course.']);

    }

    /** @test */
    public function owner_can_get_grader_permissions()
    {
        $this->assignment->graders()->attach($this->grader_user);

        $this->actingAs($this->user)->getJson("/api/grader-permissions/{$this->course->id}")
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function non_owner_cannot_update_course_level_grader_permissions()
    {
        $this->actingAs($this->user_2)->patchJson("/api/grader-permissions/course/{$this->course->id}/1")
            ->assertJson(['message' => 'You are not allowed to grant access to all assignments for all graders for this course.']);
    }



}
