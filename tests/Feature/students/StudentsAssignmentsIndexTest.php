<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use App\Course;
use App\Assignment;
use App\Enrollment;
use App\SubmissionFile;

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

        //student not enrolled
        $this->student_user_3 = factory(User::class)->create();
        $this->student_user_3->role = 3;
        $this->assignment_file = factory(SubmissionFile::class)->create(['type' => 'assignment', 'user_id' => $this->student_user->id]);
    }

    /** @test */
    public function assignment_file_must_contain_a_file()
    {

        $this->actingAs($this->student_user_2)->putJson("/api/submission-files", [
            'assignmentFile' => '',
            'assignmentId' => $this->assignment->id,
            'type' => 'assignment'
        ])
            ->assertJson(['type' => 'error', 'message' => 'The assignment file field is required.']);

    }

    /** @test */
    public function cannot_upload_if_past_due(){
        $assignment_due = $this->assignment->due;
        $this->assignment->due = '2020-06-12 09:00:00';
        $this->assignment->save();

     $this->actingAs($this->student_user)->putJson("/api/submission-files", [
            'assignmentFile' => 'abd.pdf',
            'assignmentId' => $this->assignment->id,
        ])
            ->assertJson(['type' => 'error',
                'message' => 'You cannot upload a file since this assignment is past due.']);

    }

    /** @test */
    public function can_get_assignment_file_info_if_owner()
    {
        $this->actingAs($this->student_user)->getJson("/api/assignment-files/assignment-file-info-by-student/{$this->assignment->id}")
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function cannot_get_assignment_file_info_if_not_owner()
    {
        $this->actingAs($this->student_user_2)->getJson("/api/assignment-files/assignment-file-info-by-student/{$this->assignment->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to get the information on this file submission.']);
    }

    /** @test */
    public function cannot_download_assignment_file_if_not_owner()
    {
        $this->actingAs($this->student_user_2)->postJson("/api/submission-files/download",
            [
                'assignment_id' => $this->assignment->id,
                'submission' => $this->assignment_file->submission
            ]
        )
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to download that assignment file.']);

    }




    /** @test */
    public function assignment_file_must_contain_a_pdf_file()
    {

        $this->actingAs($this->student_user_2)->putJson("/api/submission-files", [
            'assignmentFile' => 'sdflkj.jpeg',
            'assignmentId' => $this->assignment->id,
            'type' => 'assignment'
        ])
            ->assertJson(['type' => 'error', 'message' => 'The assignment file must be a file of type: pdf.']);


    }

    /** @test */
    public function cannot_store_assignment_file_if_not_enrolled_in_course()
    {

        $this->actingAs($this->student_user_3)->putJson("/api/submission-files", [
            'assignmentFile' => 'abd.pdf',
            'assignmentId' => $this->assignment->id,
        ])
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to upload a file to this assignment.']);
    }

    /** @test */
    public function can_store_assignment_file_if_enrolled_in_course()
    {

        $this->markTestIncomplete(
            'https://laravel.com/docs/7.x/http-tests#testing-file-uploads'
        );

    }
}
