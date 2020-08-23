<?php

namespace Tests\Feature\instructors;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use App\Course;
use App\Assignment;
use App\Enrollment;
use App\AssignmentFile;

class AssignmentFileTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create(['id' => 2]);
        $this->course = factory(Course::class)->create();
        $this->assignment = factory(Assignment::class)->create();

        //create a student and enroll in the class
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);

        $this->assignment_file = factory(AssignmentFile::class)->create(['user_id' => $this->student_user->id]);


    }

    /** @test */

    public function can_get_assignment_files_if_owner()
    {

        $this->actingAs($this->user)->getJson("/api/assignment-files/{$this->assignment->id}")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_get_assignment_files_if_not_owner()
    {
        $this->actingAs($this->user_2)->getJson("/api/assignment-files/{$this->assignment->id}")
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to access these assignment files.']);

    }

    /** @test */
    public function can_download_assignment_file_if_owner()
    {
        //not sure how to test

    }

    /** @test */
    public function cannot_download_assignment_file_if_not_owner()
    {
        $this->actingAs($this->user_2)->postJson("/api/assignment-files/download",
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
        $this->actingAs($this->user)->postJson("/api/assignment-files/get-temporary-url-from-request",
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
        $this->actingAs($this->user_2)->postJson("/api/assignment-files/get-temporary-url-from-request",
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
        $this->actingAs($this->user)->postJson("/api/assignment-files/text-feedback",
            [
                'assignment_id' => $this->assignment->id,
                'user_id' => $this->student_user->id,
                'textFeedback' => 'Some text feedback'
            ]
        )
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_store_text_feedback_if_not_owner()
    {
        $this->actingAs($this->user_2)->postJson("/api/assignment-files/text-feedback",
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
