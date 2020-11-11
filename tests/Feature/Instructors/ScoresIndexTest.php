<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Score;
use Illuminate\Support\Facades\DB;
use App\AssignmentGroupWeight;
use App\Course;
use App\Enrollment;
use App\User;
use App\Question;
use App\Extension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ScoresIndexTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        //enroll a student in that course
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;

        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);
    }

    function question()
    {
        return $question = factory(Question::class)->create(['page_id' => rand(1, 1000000000)]);
    }

    function createAssignmentGroupWeightsAndAssignments()
    {


        //2 groups of assignments
        AssignmentGroupWeight::create([
            'course_id' => $this->course->id,
            'assignment_group_id' => 1,
            'assignment_group_weight' => 10
        ]);

        AssignmentGroupWeight::create([
            'course_id' => $this->course->id,
            'assignment_group_id' => 2,
            'assignment_group_weight' => 90
        ]);

        //GROUP 1
//assignment has 1 question
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question()->id,
            'points' => 2
        ]);


        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'score' => 2
        ]);


        //assignment 1 has 3 questions
        $this->assignment_1 = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'name' => 'Assignment_1',
            'assignment_group_id' => 1
        ]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_1->id,
            'question_id' => $this->question()->id,
            'points' => 10
        ]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_1->id,
            'question_id' => $this->question()->id,
            'points' => 20
        ]);

        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment_1->id,
            'score' => 5
        ]);
        //Assignment 1: 5/30

        //GROUP 1
        //assignment 2 has 2 questions
        $this->assignment_2 = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'name' => 'Assignment_2',
            'assignment_group_id' => 1
        ]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_2->id,
            'question_id' => $this->question()->id,
            'points' => 1
        ]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_2->id,
            'question_id' => $this->question()->id,
            'points' => 2
        ]);

        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment_2->id,
            'score' => 2
        ]);

        //Assignment 2: 2/3

        //GROUP 2
        //assignment 3 has 2 questions
        $this->assignment_3 = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'name' => 'Assignment_3',
            'assignment_group_id' => 2
        ]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_3->id,
            'question_id' => $this->question()->id,
            'points' => 50
        ]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_3->id,
            'question_id' => $this->question()->id,
            'points' => 50
        ]);

        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment_3->id,
            'score' => 25
        ]);

        //Assignment 3: 25/100
        $this->assignment_4 = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'name' => 'Assignment_4',
            'assignment_group_id' => 2,
            'source' => 'x',
            'external_source_points' => 100
        ]);

        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment_4->id,
            'score' => 75
        ]);

        //GROUP 1 scores: 5/30 and 2/3 weight of 10
        //GROUP 2 scores: 25/30 weight of 90
        //10*((2/2 + 5/30 + 2/3)/3)+90*(.5*(25/100 + 75/100))=51.11%


    }

    /** @test */

    public function correctly_computes_the_final_scores()
    {

        //4 assignments with 2 different weights
        $this->createAssignmentGroupWeightsAndAssignments();
        $response = $this->actingAs($this->user)->getJson("/api/scores/{$this->course->id}");
        $this->assertEquals('51.11%', $response->baseResponse->original['table']['rows'][0][6]);//see computation above

    }

    /** @test */
    public function can_update_assignment_score_if_owner()
    {
        $this->actingAs($this->user)->patchJson("/api/scores/{$this->assignment->id}/{$this->student_user->id}",
            [
                'score' => 3
            ])
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function cannot_update_assignment_score_if_not_owner()
    {
        $this->actingAs($this->user_2)->patchJson("/api/scores/{$this->assignment->id}/{$this->student_user->id}",
            [
                'score' => 3
            ])
            ->assertJson([
                'type' => 'error',
                'message' => 'You are not allowed to update this score.']);
    }

    /** @test */
    public function can_update_or_add_extension_if_owner()
    {
        $this->actingAs($this->user)->postJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}",
            [
                'extension_date' => '2025-09-02',
                'extension_time' => '09:00:00'
            ])
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function extension_date_cannot_be_in_the_past()
    {
        $this->actingAs($this->user)->postJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}",
            [
                'extension_date' => '2019-09-02',
                'extension_time' => '09:00:00'
            ])
            ->assertJsonValidationErrors('extension_date');
    }

    /** @test */
    public function extension_time_must_be_a_time()
    {
        $this->actingAs($this->user)->postJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}",
            [
                'extension_date' => '2029-09-02',
                'extension_time' => 'not a time'
            ])
            ->assertJsonValidationErrors('extension_time');
    }

    /** @test */
    public function cannot_update_or_add_extension_if_not_owner()
    {
        $this->actingAs($this->user_2)->postJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}",
            [
                'extension_date' => '2025-09-02',
                'extension_time' => '09:00:00'
            ])
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to create an extension for this student/assignment.']);

    }

    public function creatExtensionForTesting()
    {
        //create an extension
        return factory(Extension::class)->create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->student_user->id
        ]);
    }


    /** @test */
    public function can_get_extension_if_owner()
    {
        $this->creatExtensionForTesting();
        $this->actingAs($this->user)->getJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}")
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function cannot_get_extension_for_student_if_not_owner()
    {
        $this->creatExtensionForTesting();
        $this->actingAs($this->user_2)->getJson("/api/extensions/{$this->assignment->id}/{$this->student_user->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to view this extension.']);

    }

    /** @test */
    public function can_get_course_scores_if_owner()
    {
        $this->actingAs($this->user)->getJson("/api/scores/{$this->course->id}")
            ->assertJson(['hasAssignments' => true]);//for the fake student
    }

    /** @test */
    public function cannot_get_course_scores_if_not_owner()
    {

        $this->actingAs($this->user_2)->getJson("/api/scores/{$this->course->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to view these scores.']);//for the fake student

    }

    /** @test */
    public function can_get_student_score_by_assignment_if_owner()
    {

    }

    /** @test */
    public
    function cannot_get_student_score_by_assignment_if_not_owner()
    {

    }

    /** @test */

    public function correctly_handles_different_timezones()
    {

    }


}
