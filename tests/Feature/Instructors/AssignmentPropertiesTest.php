<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Grader;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AssignmentsPropertiesTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);

        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;

    }
    /** @test * */
    public function the_correct_formatted_late_policy_is_retrieved_for_one_deduction_per_period()
    {
        $this->assignment->late_policy = 'deduction';
        $this->assignment->late_deduction_application_period = '2 hours';
        $this->assignment->late_deduction_percent = 20;
        $this->assignment->late_policy_deadline = '2027-06-12 02:00:00';
        $this->assignment->save();

        $response['assignment'] = ['late_policy' => "A deduction of 20% is applied every 2 hours to any late assignment.  Students cannot submit assessments later than June 11, 2027 7:00:00 pm."];
        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/summary")
            ->assertJson($response);
    }

    /** @test * */
    public function the_correct_formatted_late_policy_is_retrieved_for_once_deduction()
    {
        $this->assignment->late_policy = 'deduction';
        $this->assignment->late_deduction_application_period = 'once';
        $this->assignment->late_deduction_percent = 20;
        $this->assignment->late_policy_deadline = '2027-06-12 02:00:00';
        $this->assignment->save();

        $response['assignment'] = ['late_policy' => "A deduction of 20% is applied once to any late assignment.  Students cannot submit assessments later than June 11, 2027 7:00:00 pm."];
        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/summary")
            ->assertJson($response);
    }

    /** @test * */
    public function the_correct_formatted_late_policy_is_retrieved_for_not_accepted()
    {
        $this->assignment->late_policy = 'not accepted';
        $this->assignment->save();
        $response['assignment']  = ['late_policy' => "No late assignments are accepted."];
        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/summary")
            ->assertJson($response);

    }



}
