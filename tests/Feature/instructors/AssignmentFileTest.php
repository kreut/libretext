<?php

namespace Tests\Feature\instructors;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use App\Course;
use App\Assignment;
use App\Enrollment;
use App\SubmissionFile;

class AssignmentFileTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();

        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

        //create a student and enroll in the class
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;

        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);

        $this->assignment_file = factory(SubmissionFile::class)->create(['type'=>'a', 'user_id' => $this->student_user->id, 'assignment_id' => $this->assignment->id]);


    }

    /** @test */
    public function cannot_get_assignment_files_if_not_owner()
    {
        $this->actingAs($this->user_2)->getJson("/api/submission-files/assignment/{$this->assignment->id}")
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to access these assignment files.']);

    }

    /** @test */

    public function can_get_assignment_files_if_owner()
    {

        $this->actingAs($this->user)->getJson("/api/submission-files/assignment/{$this->assignment->id}")
            ->assertJson(['type' => 'success']);

    }



    /** @test */
    public function cannot_get_assignment_files_if_assignment_files_not_enabled()
    {
        $this->assignment->submission_files = '0';
        $this->assignment->save();
        $this->actingAs($this->user)->getJson("/api/submission-files/assignment/{$this->assignment->id}")
            ->assertJson(['type' => 'error', 'message' => 'This assignment currently does not have assignment uploads enabled.  Please edit the assignment in order to view this screen.']);

    }


    /** @test */
    public function can_download_assignment_file_if_owner()
    {
        $this->markTestIncomplete(
            'Not sure how to test'
        );

    }

    /** @test */
    public function cannot_download_assignment_file_if_not_owner()
    {
        $this->actingAs($this->user_2)->postJson("/api/submission-files/download",
            [
                'assignment_id' => $this->assignment->id,
                'submission' => $this->assignment_file->submission
            ]
        )
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to download that assignment file.']);

    }

    /** @test */
    public function can_get_temporary_url_from_request_if_owner()
    {
        $this->actingAs($this->user)->postJson("/api/submission-files/get-temporary-url-from-request",
            [
                'assignment_id' => $this->assignment->id,
                'file' => $this->assignment_file->submission
            ]
        )
            ->assertJson(['type' => 'success']);

    }


    /** @test */
    public function cannot_get_temporary_url_if_not_owner()
    {
        $this->actingAs($this->user_2)->postJson("/api/submission-files/get-temporary-url-from-request",
            [
                'assignment_id' => $this->assignment->id,
                'file' => $this->assignment_file->submission
            ]
        )
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to create a temporary URL.']);

    }

    /** @test */
    public function can_store_text_feedback_if_owner()
    {
        $this->actingAs($this->user)->postJson("/api/submission-files/text-feedback",
            [
                'assignment_id' => $this->assignment->id,
                'user_id' => $this->student_user->id,
                'type' => 'assignment',
                'textFeedback' => 'Some text feedback'
            ]
        )
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_store_text_feedback_if_not_owner()
    {
        $this->actingAs($this->user_2)->postJson("/api/submission-files/text-feedback",
            [
                'assignment_id' => $this->assignment->id,
                'user_id' => $this->student_user->id,
                'textFeedback' => 'Some text feedback'
            ]
        )
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to submit comments for this assignment.']);

    }


}
