<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Question;
use App\Score;
use App\Section;
use App\Submission;
use App\SubmissionFile;
use App\Traits\Test;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubmissionsTest extends TestCase
{

    use Test;

    private $student_user;
    private $assignment;
    private $question;
    private $student_user_2;
    private $user;
    /**
     * @var Collection|Model|mixed
     */
    private $section;
    /**
     * @var Collection|Model|mixed
     */
    private $course;
    private $scores;

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
        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id, $this->student_user->id);

        $this->question = factory(Question::class)->create(['page_id' => 16251]);
        $this->question_2 = factory(Question::class)->create(['page_id' => 2]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 10
        ]);

        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;


        $this->scores = ['new_score' => 10,
            'apply_to' => 1,
            'user_ids' => [$this->student_user->id],
            'type' => 'Auto-graded'];

    }
    /** @test */
    public function return_if_the_new_score_goes_over_the_points_for_the_question(){
        $this->scores['new_score'] = 20;
        $this->createSubmission($this->student_user, 5);
        $this->actingAs($this->user)->postJson("/api/scores/over-total-points/{$this->assignment->id}/{$this->question->id}", $this->scores)
            ->assertJson(['num_over_max' => 1]);
    }



    function createSubmission($student, $score)
    {
        $submission = new Submission();
        $submission->submission = '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":11,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}';
        $submission->assignment_id = $this->assignment->id;
        $submission->question_id = $this->question->id;
        $submission->score = $score;
        $submission->user_id = $student->id;
        $submission->submission_count = 1;
        $submission->answered_correctly_at_least_once = true;
        $submission->save();

    }

    function createScore($student, $original_assignment_score)
    {
        $score = new Score();
        $score->assignment_id = $this->assignment->id;
        $score->user_id = $student->id;
        $score->score = $original_assignment_score;
        $score->save();
    }

    /** @test */
    public function apply_to_filter_only_affects_students_in_post()
    {
        //need 1 submission in post and 1 not in post
        //need 2 assignment scores
        $original_assignment_score = 5;
        $original_assessment_score = 2;
        $this->createSubmission($this->student_user, $original_assessment_score);
        $this->createSubmission($this->student_user_2, $original_assessment_score);
        $this->createScore($this->student_user, $original_assignment_score);
        $this->createScore($this->student_user_2, $original_assignment_score);

        $this->scores['new_score'] = 4;
        $this->actingAs($this->user)->patchJson("/api/submissions/{$this->assignment->id}/{$this->question->id}/scores", $this->scores);

        $score = new Score();
        $new_score = $score->where('user_id', $this->student_user->id)->first();
        $this->assertEquals($original_assignment_score + $this->scores['new_score'] - $original_assessment_score, $new_score->score, 'Adds the correct adjustment');

        $new_score = $score->where('user_id', $this->student_user_2->id)->first();
        $this->assertEquals($original_assignment_score, $new_score->score, 'Does not touch the other student');

    }

    /** @test */
    public function update_works_for_file_submissions()
    {

        $original_assignment_score = 5;
        $original_assessment_score = 2;
        SubmissionFile::create($this->submission_file = [
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'type' => 'q',
            'user_id' => $this->student_user->id,
            'original_filename' => 'blah blah',
            'submission' => 'sflkjfwlKEKLie.jpg',
            'score' =>  $original_assessment_score,
            'date_submitted' => Carbon::now()
        ]);
        $this->createScore($this->student_user, $original_assignment_score);

        $this->scores['new_score'] = 4;
        $this->actingAs($this->user)->patchJson("/api/submission-files/{$this->assignment->id}/{$this->question->id}/scores", $this->scores);

        $score = new Score();
        $new_score = $score->where('user_id', $this->student_user->id)->first();
        $this->assertEquals($original_assignment_score + $this->scores['new_score'] - $original_assessment_score, $new_score->score, 'Adds the correct adjustment');

    }


    /** @test */
    public function owner_can_get_auto_graded_submissions()
    {
        $this->actingAs($this->user)->getJson("/api/scores/{$this->assignment->id}/{$this->question->id}/get-scores-by-assignment-and-question")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function non_owner_cannot_get_auto_graded_submissions()
    {
        $this->actingAs($this->student_user)->getJson("/api/scores/{$this->assignment->id}/{$this->question->id}/get-scores-by-assignment-and-question")
            ->assertJson(['message' => "You can't get the scores for an assignment that is not in one of your courses."]);

    }


    /** @test */
    public function non_owner_cannot_update_auto_graded_submissions()
    {

        $this->actingAs($this->student_user)->patchJson("/api/submissions/{$this->assignment->id}/{$this->question->id}/scores", $this->scores)
            ->assertJson(['message' => "You can't update the scores for an assignment not in one of your courses."]);


    }

    /** @test */
    public function score_must_be_valid()
    {
        $this->scores['new_score'] = -1;
        $this->actingAs($this->user)->patchJson("/api/submissions/{$this->assignment->id}/{$this->question->id}/scores", $this->scores)
            ->assertJsonValidationErrors('new_score');

        $this->scores['new_score'] = "some letters";
        $this->actingAs($this->user)->patchJson("/api/submissions/{$this->assignment->id}/{$this->question->id}/scores", $this->scores)
            ->assertJsonValidationErrors('new_score');

    }

    /** @test */
    public function owner_can_update_auto_graded_submissions()
    {
        $this->actingAs($this->user)->patchJson("/api/submissions/{$this->assignment->id}/{$this->question->id}/scores", $this->scores)
            ->assertJson(['message' => "The scores have been updated."]);
    }



}
