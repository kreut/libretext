<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Grader;
use App\LearningTree;
use App\Question;
use App\Section;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssignmentPropertiesTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;

        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);



        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;

    }

    /** @test */

    public function user_cannot_change_to_external_if_a_question_already_exists()
    {
        $this->question = factory(Question::class)->create(['page_id' => 1]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);
        $this->assignment->source = 'a';
        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/validate-assessment-type",
            ['source' => 'x'])
            ->assertJson(['message' => "You can't switch to an external assignment until you remove all Adapt questions from the assignment."]);
    }

    /** @test */

    public function user_cannot_change_to_non_delayed_if_there_are_open_ended_questions()
    {
        $this->question = factory(Question::class)->create(['page_id' => 1]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);

        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/validate-assessment-type",
            ['assessment_type' => 'real time'])
            ->assertJson(['message' => "You can't switch to a real time assessment type until you remove the open-ended questions from the assignment."]);
    }

    /** @test */

    public function user_cannot_change_to_a_non_learning_tree_from_learning_tree_if_learning_tree_exists()
    {
        $this->question = factory(Question::class)->create(['page_id' => 1]);

        $assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 0
        ]);
        $this->learning_tree = factory(LearningTree::class)->create(['user_id' => $this->user->id]);
        //create a student and enroll in the class
        DB::table('assignment_question_learning_tree')->insertGetId([
            'assignment_question_id' => $assignment_question_id,
            'learning_tree_id' => $this->learning_tree->id
        ]);
        $this->assignment->assessment_type = 'learning tree';
        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/validate-assessment-type",
            ['assessment_type' => 'real time'])
            ->assertJson(['message' => "You can't switch to a real time assessment type since this is not a learning tree assignment."]);
    }


    /** @test */

    public function user_cannot_change_to_learning_tree_if_regular_question_exists()
    {
        $this->question = factory(Question::class)->create(['page_id' => 1]);

      DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 0
        ]);

        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/validate-assessment-type",
            ['assessment_type' => 'learning tree'])
            ->assertJson(['message' => "You can't switch to a learning tree assessment type since this is not a learning tree assignment and you already have non-learning tree questions."]);
    }

    /** @test * */
    public function the_correct_formatted_late_policy_is_retrieved_for_one_deduction_per_period()
    {
        $this->assignment->late_policy = 'deduction';
        $this->assignment->late_deduction_application_period = '2 hours';
        $this->assignment->late_deduction_percent = 20;
        $this->assignment->final_submission_deadline = '2027-06-12 02:00:00';
        $this->assignment->save();

        $response['assignment'] = ['formatted_late_policy' => "A deduction of 20% is applied every 2 hours to any late assignment.  Students cannot submit assessments later than June 11, 2027 7:00:00 pm."];
        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/summary")
            ->assertJson($response);
    }

    /** @test * */
    public function the_correct_formatted_late_policy_is_retrieved_for_once_deduction()
    {
        $this->assignment->late_policy = 'deduction';
        $this->assignment->late_deduction_application_period = 'once';
        $this->assignment->late_deduction_percent = 20;
        $this->assignment->final_submission_deadline = '2027-06-12 02:00:00';
        $this->assignment->save();

        $response['assignment'] = ['formatted_late_policy' => "A deduction of 20% is applied once to any late assignment.  Students cannot submit assessments later than June 11, 2027 7:00:00 pm."];
        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/summary")
            ->assertJson($response);
    }

    /** @test * */
    public function the_correct_formatted_late_policy_is_retrieved_for_not_accepted()
    {
        $this->assignment->late_policy = 'not accepted';
        $this->assignment->save();
        $response['assignment'] = ['formatted_late_policy' => "No late assignments are accepted."];
        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/summary")
            ->assertJson($response);

    }


}
