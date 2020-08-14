<?php

namespace Tests\Feature;

use App\Course;
use App\User;
use App\Assignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InstructorsAssignmentsTest extends TestCase
{

    /**Still must test the stuff with the correct/completed and number**/
    /** Should test that only an instructor can create an assignment... */
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create();
    }

    /** @test */
    public function can_visit_instructors_assignments()
    {
        $response = $this->getJson('/instructors/assignments');
        $response->assertStatus(200);
    }


   /** @test */
    public function can_get_your_assignments()
    {


        factory(Assignment::class)->create(['course_id' => $this->course->id,
            'name' => 'First Assignment',
            'available_from' => '2020-06-10 09:00:00',
            'due' => '2020-06-12 09:00:00',
            'num_submissions_needed' => 3,
            'type_of_submission' => 'correct']);

        $this->actingAs($this->user)->getJson("/api/assignments/courses/{$this->course->id}")
            ->assertJson([['id'=> 1]]);

    }


    public function cannot_get_assignments_if_you_are_a_student()
    {
        $this->user->role = 3;
        $this->actingAs($this->user)->getJson("/api/courses")
            ->assertJson(['type' => 'error', 'message'=> 'You are not allowed to view courses.']);

    }


    public function can_delete_an_assignment_if_you_are_the_owner()
    {

    }


    public function cannot_delete_an_assignment_if_you_are_not_the_owner()
    {

    }


    public function can_update_an_assignmenbt_if_you_are_the_owner()
    {

    }


    public function cannot_update_an_assignmenbt_if_you_are_the_owner()
    {

    }


    public function can_create_an_assignment()
    {
        $course = factory(Course::class)->create(['user_id' => $this->user->id,
            'name' => 'First Course',
            'start_date' => '2020-06-10',
            'end_date' => '2021-06-10']);

        $this->actingAs($this->user)->postJson("/api/assignments",['course_id' => $course->id,
            'name' => 'First Assignment',
            'available_from_date' => '2020-06-10',
            'available_from_time' => '09:00:00',
            'due_date' => '2020-06-12',
            'due_time' => '09:00:00',
            'num_submissions_needed' => 3,
            'type_of_submission' => 'correct'])
            ->assertJson(['type' => 'success']);

    }


    public function must_include_an_assignment_name()
    {


    }


    public function must_include_valid_available_on_date()
    {


    }


    public function must_include_valid_due_date()
    {
    }


    public function must_include_valid_due_time()
    {
    }


    public function due_date_must_be_after_available_date()
    {
    }


    public function must_include_valid_available_time()
    {
    }


}
