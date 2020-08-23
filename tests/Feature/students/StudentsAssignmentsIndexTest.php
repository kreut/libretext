<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use App\Course;
use App\Assignment;
use App\Enrollment;
use App\AssignmentFile;

class StudentsAssignmentsIndexTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create();
        $this->assignment = factory(Assignment::class)->create();

        //create a student and enroll in the class
        $this->student_user = factory(User::class)->create();
        $this->student_user_2 = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->student_user_2->role = 3;
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user_2->id,
            'course_id' => $this->course->id
        ]);
        $this->assignment_file = factory(AssignmentFile::class)->create(['user_id' => $this->student_user->id]);
    }



    /** @test */
   public function can_get_assignment_file_info_if_owner() {

   }
    /** @test */
    public function cannot_get_assignment_file_info_if_owner() {

    }

    /** @test */
    public function cannot_download_assignment_file_if_not_owner()
    {
        $this->actingAs($this->student_user_2)->postJson("/api/assignment-files/download",
            [
                'assignment_id' =>  $this->assignment->id,
                'submission' => $this->assignment_file->submission
            ]
        )
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to download that assignment file.']);

    }


    public function can_store_assignment_file_if_enrolled_in_course() {

    }

    public function cannot_store_assignment_file_if_not_enrolled_in_course(){

}

}
