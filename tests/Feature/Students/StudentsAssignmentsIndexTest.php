<?php

namespace Tests\Feature\Students;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use App\Course;
use App\Assignment;
use App\Enrollment;
use App\SubmissionFile;
use App\Traits\Test;

class StudentsAssignmentsIndexTest extends TestCase
{
    use Test;
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id'=> $this->course->id,  'show_scores' => 1]);

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
        $this->submission_file = factory(SubmissionFile::class)
                                        ->create([
                                            'assignment_id' => $this->assignment->id,
                                            'type' => 'a',
                                            'user_id' => $this->student_user->id
                                        ]);
    }


/** @test */
    public function correctly_computes_the_final_score_for_the_student_if_not_all_assignments_show_scores()
    {
        //4 assignments with 2 different weights
        $this->assignment->show_scores = false;
        $this->assignment->save();
        $this->createAssignmentGroupWeightsAndAssignments();
        $this->actingAs($this->student_user)->getJson("/api/scores/{$this->course->id}/get-scores-by-user")
            ->assertJson(['weighted_score' => '51.11%']);

    }

    /** @test */

    public function correctly_computes_the_final_score_for_the_student_if_all_assignments_show_scores()
    {
        //4 assignments with 2 different weights
        $this->createAssignmentGroupWeightsAndAssignments();
        $this->actingAs($this->student_user)->getJson("/api/scores/{$this->course->id}/get-scores-by-user")
            ->assertJson(['weighted_score' => '51.11%']);

    }

    /** @test */

    public function must_be_enrolled_in_the_course_to_view_the_score()
    {

        $this->createAssignmentGroupWeightsAndAssignments();
        $this->actingAs($this->student_user_3)->getJson("/api/scores/{$this->course->id}/get-scores-by-user")
          ->assertJson(['You are not allowed to view this score.']);

        dd('also make sure that allowed at the course level');

    }





    /** @test */
    public function assignment_file_must_contain_a_file()
    {

        $this->actingAs($this->student_user_2)->putJson("/api/submission-files", [
            'submissionFile' => '',
            'assignmentId' => $this->assignment->id,
            'uploadLevel' => 'assignment'
        ])
            ->assertJson(['type' => 'error', 'message' => 'The submission file field is required.']);

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
   /*    need exception... $this->actingAs($this->student_user_2)->postJson("/api/submission-files/download",
            [
                'assignment_id' => $this->assignment->id,
                'submission' => $this->submission_file->submission
            ]
        )
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to download that assignment file.']);
*/
    }




    /** @test */
    public function assignment_file_must_contain_a_pdf_file()
    {

        $this->actingAs($this->student_user_2)->putJson("/api/submission-files", [
            'submissionFile' => 'sdflkj.ziggy',
            'assignmentId' => $this->assignment->id,
            'uploadLevel' => 'assignment'
        ])
            ->assertJson(['type' => 'error', 'message' => 'The submission file must be a file of type: pdf, txt, png, jpeg, jpg.']);


    }
    /** @test */

    public function correctly_handles_different_timezones() {

    }


    /** @test */
    public function cannot_store_assignment_file_if_not_enrolled_in_course()
    {

        $this->actingAs($this->student_user_3)->putJson("/api/submission-files", [
            'submissionFile' => 'abd.pdf',
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
