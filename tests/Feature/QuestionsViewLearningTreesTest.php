<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Extension;
use App\Cutup;
use App\Section;
use App\User;
use App\Question;
use App\SubmissionFile;
use Carbon\Carbon;
use App\Score;
use App\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

use App\Traits\Test;

class QuestionsViewLearningTreesTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;


        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);


        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id,
            'solutions_released' => 0,
            'assessment_type' => 'learning tree',
            'submission_count_percent_decrease' => 10,
            'percent_earned_for_exploring_learning_tree' => 50]);
        $this->assignUserToAssignment($this->assignment->id, $this->course->id, $this->student_user->id);
        $this->question = factory(Question::class)->create(['page_id' => 1]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'open_ended_submission_type' => 'none',
            'order'=> 1,
            'points' => 10
        ]);



        $this->cutup = factory(Cutup::class)->create(['user_id' => $this->student_user->id, 'assignment_id' => $this->assignment->id]);


        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;


        $this->correctSubmission= [
            'technology' => 'h5p',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission' => '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":11,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}'
        ];

        $this->incorrectSubmission = [
            'technology' => 'h5p',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission' => '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":3,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}'
        ];
    }

    /** @test */
    public function incorrect_responses_still_get_learning_tree_points_as_the_score_if_explored_learning_tree()
    {


        $this->actingAs($this->student_user)
            ->postJson("/api/submissions", $this->incorrectSubmission );
        $submission = Submission::latest()->first();
        $submission->explored_learning_tree = 1;
        $submission->save();
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->incorrectSubmission )
            ->assertJson(['message' => "Incorrect! But you're still receiving 5 points for exploring the Learning Tree."]);
    }



    /** @test */
    public function student_in_course_can_update_explored_learning_tree()
    {
        $this->actingAs($this->student_user)->patchJson("api/submissions/{$this->assignment->id}/{$this->question->id}/explored-learning-tree")
            ->assertJson(['explored_learning_tree' => true]);
    }

    /** @test */
    public function non_student_in_course_cannot_update_explored_learning_tree()
    {

        $this->actingAs($this->student_user_2)->patchJson("api/submissions/{$this->assignment->id}/{$this->question->id}/explored-learning-tree")
            ->assertJson(['message' => 'No responses will be saved since you were not assigned to this assignment.']);
    }

    /** @test */
    public function if_they_get_it_correct_on_the_first_attempt_they_just_get_the_full_score()
    {


        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->correctSubmission)
            ->assertJson(['message' => 'Question submission saved. Your scored was updated.']);
    }

    /** @test */
    public function if_they_have_not_explored_the_learning_tree_and_they_did_not_get_the_question_correct_there_is_no_update()
    {
       $this->actingAs($this->student_user)->postJson("/api/submissions", $this->incorrectSubmission );

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->incorrectSubmission )
            ->assertJson(['message' => 'You can resubmit after spending time exploring the Learning Tree.']);

    }



    /** @test */
    public function correct_penalty_applied_based_on_penalty_percent_and_submission_count()
    {
        //no penalty
       $this->actingAs($this->student_user)->postJson("/api/submissions", $this->incorrectSubmission );
        $submission = Submission::latest()->first();
        $submission->explored_learning_tree = 1;
        $submission->save();
        //no penalty
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->incorrectSubmission );
        //start taking off the penalty
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->correctSubmission)
            ->assertJson(['message' => 'Your total score was updated with a penalty of 10% applied.']);


    }



    /** @test */
    public function students_can_resubmit_only_after_visiting_learning_tree_at_least_once()
    {

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->correctSubmission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->correctSubmission)
            ->assertJson(['message' => 'You can resubmit after spending time exploring the Learning Tree.']);

    }

    /** @test */
    public function learning_tree_assessment_scores_not_updated_if_already_answered_correctly()
    {

        //submit correct submission;

       $this->actingAs($this->student_user)->postJson("/api/submissions", $this->correctSubmission);
         $submission = Submission::latest()->first();
        $submission->explored_learning_tree = 1;
         $submission->save();

         $this->actingAs($this->student_user)->postJson("/api/submissions", $this->correctSubmission)
           ->assertJson(['message' => 'Your score was not updated since you already answered this question correctly.']);

    }







}
