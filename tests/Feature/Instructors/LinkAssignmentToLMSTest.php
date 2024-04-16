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

class LinkAssignmentToLMSTest extends TestCase
{

    use Test;

    /**Still must test the stuff with the correct/completed and number**/
    /** Should test that only an instructor can create an assignment... */
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
    public function non_owner_cannot_unlink_assignment_from_lms()
    {

        $this->actingAs($this->user_2)
            ->patchJson("/api/assignments/{$this->assignment->id}/unlink-from-lms")
            ->assertJson(['message' => "You are not allowed to unsync this assignment from your LMS."]);

    }

    /** @test */
    public function non_owner_cannot_link_assignment_to_lms()
    {

        $this->actingAs($this->user_2)
            ->postJson("/api/lti/link-assignment-to-lms/{$this->assignment->id}")
            ->assertJson(['message' => "You are not allowed to link this assignment."]);

    }

    /** @test */

    public function owner_can_link_assignment_to_lms()
    {

        $this->actingAs($this->user)
            ->postJson("/api/lti/link-assignment-to-lms/{$this->assignment->id}", ['lms_resource_link_id' => 1])
            ->assertJson(["type" => "success"]);

    }

    /** @test */

    public function owner_cannot_link_multiple_assignments_to_the_same_resource_id()
    {
        $this->assignment->lms_resource_link_id = 1;
        $this->assignment->save();
        $this->actingAs($this->user)
            ->postJson("/api/lti/link-assignment-to-lms/{$this->assignment_2->id}", ['lms_resource_link_id' => 1])
            ->assertJson(["message" => "That LMS resource is already linked to another assignment."]);

    }

}
