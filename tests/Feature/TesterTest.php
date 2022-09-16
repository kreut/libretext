<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Question;
use App\Section;
use App\Submission;
use App\TesterCourse;
use App\TesterStudent;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TesterTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['role' => 3, 'testing_student' => 1]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        $this->tester = factory(User::class)->create(['role' => 6, 'email' => 'someeamil@hotmail.com']);
        $this->tester_course = TesterCourse::create(['course_id' => $this->course->id,
            'user_id' => $this->tester->id]);
        TesterStudent::create(['tester_user_id' => $this->tester->id,
            'student_user_id' => $this->student_user->id,
            'section_id' => $this->section->id]);
        $this->student_to_enroll = ['first_name' => 'John',
            'last_name' => 'Doe',
            'student_id' => 1231231
        ];
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

    }

    /** @test */
    public function submissions_and_testing_students_are_removed_if_option_chosen()
    {
        $this->assertDatabaseHas('users', ['testing_student' => 1]);
        $this->actingAs($this->user)
            ->deleteJson("/api/tester/course/{$this->course->id}/user/{$this->tester->id}/remove-associated-students")
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseMissing('users', ['testing_student' => 1]);

    }

    /** @test */
    public function submissions_and_testing_students_are_not_removed_if_option_not_chosen()
    {
        $this->assertDatabaseHas('users', ['testing_student' => 1]);
        $this->actingAs($this->user)
            ->deleteJson("/api/tester/course/{$this->course->id}/user/{$this->tester->id}/maintain-student-information")
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('users', ['testing_student' => 1]);


    }

    /** @test */
    public function will_not_remove_students_who_are_not_testing_students()
    {
        $this->student_user->testing_student = 0;
        $this->student_user->save();
        $this->actingAs($this->user)
            ->deleteJson("/api/tester/course/{$this->course->id}/user/{$this->tester->id}/remove-associated-students")
            ->assertJson(['message' => 'You cannot remove this tester because one of their students is not a testing students.  Please contact support.']);
    }

    /** @test */
    public function must_be_valid_option_for_deleting_a_tester()
    {

        $this->actingAs($this->user)
            ->deleteJson("/api/tester/course/{$this->course->id}/user/{$this->tester->id}/zig-zag")
            ->assertJson(['message' => 'zig-zag is not a valid option.']);


    }

    /** @test */
    public function tester_must_be_instructors_tester()
    {
        $this->actingAs($this->tester)
            ->deleteJson("/api/tester/course/{$this->course->id}/user/{$this->tester->id}/zig-zag")
            ->assertJson(['message' => 'You are not allowed to remove this tester.']);

        $this->tester_course->user_id = $this->student_user->id;
        $this->tester_course->save();
        $this->actingAs($this->user)
            ->deleteJson("/api/tester/course/{$this->course->id}/user/{$this->tester->id}/zig-zag")
            ->assertJson(['message' => 'You are not allowed to remove this tester.']);

    }


    /** @test */
    public function emailer_must_be_a_tester_for_the_student()
    {

        DB::table('tester_students')->delete();
        $this->actingAs($this->tester)
            ->postJson("/api/tester/email-results/{$this->student_user->id}")
            ->assertJson(['message' => 'You are not allowed to email these student results.']);

    }

    private function create_submission($score, $question_id)
    {
        Submission::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $question_id,
            'submission' => 'some other submission',
            'score' => $score,
            'answered_correctly_at_least_once' => 0,
            'submission_count' => 1]);
    }

    private function _initScores($score_1, $score_2)
    {
        $question_1 = factory(Question::class)->create(['library' => 'chem', 'page_id' => 355295]);
        $question_2 = factory(Question::class)->create(['library' => 'chem', 'page_id' => 355296]);

        $this->create_submission($score_1, $question_1->id);
        $this->create_submission($score_2, $question_2->id);
        $section = factory(Section::class)->create(['course_id' => $this->course->id]);

    }

    /** @test */
    public function correct_information_is_emailed_to_instructor()
    {
        $score_1 = 5;
        $score_2 = 12;
        $this->_initScores($score_1, $score_2);
        $response = $this->actingAs($this->tester)
            ->postJson("/api/tester/email-results/{$this->student_user->id}")
            ->getContent();

        $this->assertEquals($score_1 + $score_2, json_decode($response)->results_info->score);


    }

    /** @test */
    public function straight_sum_is_computed_correctly()
    {
        $section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $section->id,
            'course_id' => $this->course->id
        ]);

        $score_1 = 5;
        $score_2 = 12;
        $this->_initScores($score_1, $score_2);
        $response = $this->actingAs($this->tester)
            ->getJson("/api/scores/straight-sum/{$this->course->id}")
            ->getContent();
        $this->assertEquals($score_1 + $score_2, (json_decode($response, 1)['student_results'][0]['score']));

    }

    /** @test */
    public function cannot_compute_straight_sum_if_not_tester_course()
    {
        DB::table('tester_courses')->delete();
        $this->actingAs($this->tester)
            ->getJson("/api/scores/straight-sum/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to view the straight scores for the course.']);

    }


    /** @test */
    public function a_tester_can_remove_one_of_their_students_from_the_database()
    {
        $this->actingAs($this->tester)
            ->deleteJson("/api/user/{$this->student_user->id}/course/{$this->course->id}")
            ->assertJson(['message' => "{$this->student_user->first_name} {$this->student_user->last_name} has been removed from the system."]);
        $this->assertDatabaseMissing('users', ['id' => $this->student_user->id]);
    }

    /** @test */
    public function student_must_be_of_tester_in_order_to_remove_them_from_the_database()
    {
        DB::table('tester_students')->delete();
        $this->actingAs($this->student_user)
            ->deleteJson("/api/user/{$this->student_user->id}/course/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to remove this student.']);
    }


    /** @test */
    public function non_instructor_cannot_store_testers()
    {
        DB::table('tester_courses')->delete();
        $this->actingAs($this->student_user)
            ->postJson("/api/tester", ['course_id' => $this->course->id, 'email' => $this->tester->email])
            ->assertJson(['message' => 'You are not allowed to add testers to this course.']);
    }

    /** @test */
    public function course_must_be_valid_course_to_store_tester()
    {
        $this->actingAs($this->user)
            ->postJson("/api/tester", ['course_id' => 'sdfdsfdsf', 'email' => $this->tester->email])
            ->assertJson(['message' => 'That is not a valid course.']);

    }

    /** @test */
    public function course_must_be_owned_by_instructor()
    {
        $course = factory(Course::class)->create(['user_id' => $this->user->id + 1]);
        $this->actingAs($this->user)
            ->postJson("/api/tester", ['course_id' => $course->id, 'email' => $this->tester->email])
            ->assertJson(['message' => 'You are not allowed to add testers to this course.']);

    }

    /** @test */
    public function instructor_can_get_testers()
    {
        $this->actingAs($this->user)
            ->getJson("/api/tester/{$this->course->id}")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function non_instructor_cannot_get_testers()
    {
        $this->actingAs($this->student_user)
            ->getJson("/api/tester/{$this->course->id}")
            ->assertJson(['message' => 'You are not allowed to get the testers for this course.']);

    }


    /** @test */
    public function student_info_must_be_valid()
    {

        $this->actingAs($this->tester)
            ->postJson("/api/enrollments/auto-enroll/{$this->course->id}", [])
            ->assertJsonValidationErrors(['first_name', 'last_name', 'student_id']);

    }

    /** @test */
    public function a_tester_cannot_auto_enroll_students_if_it_is_not_their_course()
    {
        DB::table('tester_courses')->delete();
        $this->actingAs($this->tester)
            ->postJson("/api/enrollments/auto-enroll/{$this->course->id}", $this->student_to_enroll)
            ->assertJson(['message' => 'You are not allowed to auto-enroll a student in this course.']);

    }

    /** @test */
    public function non_tester_cannot_auto_enroll_students()
    {
        $this->actingAs($this->user)
            ->postJson("/api/enrollments/auto-enroll/{$this->course->id}", $this->student_to_enroll)
            ->assertJson(['message' => 'You are not allowed to auto-enroll a student in this course.']);

    }


}
