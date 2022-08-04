<?php

namespace Tests\Feature\General;

use App\Assignment;
use App\AssignToTiming;
use App\BetaAssignment;
use App\Course;
use App\Enrollment;
use App\Extension;
use App\Cutup;
use App\RefreshQuestionRequest;
use App\Section;
use App\Solution;
use App\User;
use App\Question;
use App\SubmissionFile;
use Carbon\Carbon;
use App\Score;
use App\Submission;
use App\Traits\Statistics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Traits\Test;

class RefreshQuestionTest extends TestCase
{
    use Statistics;
    use Test;

    private $upload_file_data;
    private $assignment;
    private $student_user;
    /**
     * @var array
     */
    private $upload_solution_data;
    /**
     * @var array
     */
    private $upload_file_submission_data;
    private $question;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->beta_user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->admin_user = factory(User::class)->create(['id' => 5]);


        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->beta_course = factory(Course::class)->create(['user_id' => $this->beta_user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);


        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id, 'solutions_released' => 0]);
        $this->beta_assignment = factory(Assignment::class)->create(['course_id' => $this->beta_course->id]);
        $this->non_beta_assignment = factory(Assignment::class)->create(['course_id' => $this->beta_course->id]);

        DB::table('beta_assignments')->insert(['id' => $this->beta_assignment->id, 'alpha_assignment_id' => $this->assignment->id]);

        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id, $this->student_user->id);

        $this->question = factory(Question::class)->create(['page_id' => 124987]);
        $this->question_2 = factory(Question::class)->create(['page_id' => 218201]);
        $this->question_points = 10;
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => $this->question_points,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => $this->question_points,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->non_beta_assignment->id,
            'question_id' => $this->question->id,
            'points' => $this->question_points,
            'order' => 1,
            'open_ended_submission_type' => 'file'
        ]);


        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;

        $this->student_user_3 = factory(User::class)->create();
        $this->student_user_3->role = 3;


    }

    /** @test */
    public function admin_can_approve_a_refresh_question_request_even_if_there_are_submissions()
    {
        $this->submission_object = '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":11,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}';
        $this->h5pSubmission = [
            'assignment_id' => $this->beta_assignment->id,
            'question_id' => $this->question->id,
            'submission' => $this->submission_object,
            'score' => 10,
            'user_id' => $this->student_user->id,
            'submission_count' => 1,
            'answered_correctly_at_least_once' => 1,
        ];
        $this->h5pSubmission['user_id'] = $this->student_user->id;
        Submission::insert($this->h5pSubmission);

        $this->actingAs($this->admin_user)
            ->postJson("/api/questions/{$this->question->id}/refresh",
                ['update_scores' => false])
            ->assertJson(['message' => 'The question has been refreshed.   ']);

    }

    /** @test */
    public function admin_can_get_the_refresh_question_requests()
    {

        $this->actingAs($this->admin_user)
            ->getJson("/api/refresh-question-requests/")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function non_admin_cannot_get_the_refresh_question_requests()
    {
        $this->actingAs($this->user)
            ->getJson("/api/refresh-question-requests/")
            ->assertJson(['message' => 'You are not allowed to get the refresh question requests.']);

    }

    /** @test */
    public function non_admin_cannot_deny_a_refresh_question_request()
    {

        $this->actingAs($this->user)
            ->postJson("/api/refresh-question-requests/deny/{$this->question->id}")
            ->assertJson(['message' => 'You are not allowed to deny refresh question requests.']);


    }

    /** @test */
    public function admin_can_deny_a_refresh_question_request()
    {
        factory(RefreshQuestionRequest::class)->create(['question_id' => $this->question->id,
            'user_id' => $this->user_2->id]);
        $this->user->id = 5; ///this should be whatever the admin user id is
        $this->actingAs($this->user)
            ->postJson("/api/refresh-question-requests/deny/{$this->question->id}")
            ->assertJson(['message' => 'You have denied this request and the instructor has been notified by email.']);
        $this->assertDatabaseHas('refresh_question_requests', ['question_id' => $this->question->id, 'status' => 'denied']);

    }


    /** @test */
    public function cannot_refresh_if_submissions_in_other_assignments()
    {
        $this->submission_object = '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":11,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}';
        $this->h5pSubmission = [
            'assignment_id' => $this->beta_assignment->id,
            'question_id' => $this->question->id,
            'submission' => $this->submission_object,
            'score' => 10,
            'user_id' => $this->student_user->id,
            'submission_count' => 1,
            'answered_correctly_at_least_once' => 1,
        ];
        $this->h5pSubmission['user_id'] = $this->student_user->id;
        Submission::insert($this->h5pSubmission);

        $this->actingAs($this->user)
            ->postJson("/api/questions/{$this->question->id}/refresh/{$this->assignment->id}",
                ['update_scores' => false])
            ->assertJson(['message' => 'You cannot refresh this question since there are already submissions in other assignments.']);

    }


    /** @test */
    public function scores_updated_if_submissions_in_your_assignment()
    {
        $question_score = 11;
        $assignment_score = 30;
        Score::create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->student_user->id,
            'score' => $assignment_score]);
        $this->submission_object = '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":11,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}';
        $this->h5pSubmission = [
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission' => $this->submission_object,
            'score' => $question_score,
            'user_id' => $this->student_user->id,
            'submission_count' => 1,
            'answered_correctly_at_least_once' => 1,
        ];
        $this->h5pSubmission['user_id'] = $this->student_user->id;
        Submission::insert($this->h5pSubmission);
        $this->actingAs($this->user)
            ->postJson("/api/questions/{$this->question->id}/refresh/{$this->assignment->id}",
                ['update_scores' => true])
            ->assertJson(['type' => 'success']);
        $new_score = Score::first()->score;
        $this->assertEquals($assignment_score - $question_score, $new_score);

    }

    /** @test */
    public function non_instructor_cannot_make_a_refresh_question_request()
    {
        $this->actingAs($this->student_user)
            ->postJson("api/refresh-question-requests/make-refresh-question-request/{$this->question->id}",
                ['nature_of_update' => "some sort of reason"])
            ->assertJson(['message' => 'You are not allowed to make refresh question requests.']);
    }


    /** @test */
    public function instructor_can_make_a_refresh_question_request()
    {
        $this->actingAs($this->user)
            ->postJson("api/refresh-question-requests/make-refresh-question-request/{$this->question->id}",
                ['nature_of_update' => "some sort of reason"])
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function reason_for_edit_must_be_valid()
    {
        $this->actingAs($this->user)
            ->postJson("api/refresh-question-requests/make-refresh-question-request/{$this->question->id}",
                ['nature_of_update' => ""])
            ->assertJsonValidationErrors(['nature_of_update']);
    }

    /** @test */
    public function cannot_refresh_if_not_an_instructor()
    {
        $this->actingAs($this->student_user)
            ->postJson("/api/questions/{$this->question->id}/refresh/{$this->beta_assignment->id}",
                ['update_scores' => false])
            ->assertJson(['message' => 'You are not allowed to refresh questions.']);
    }

    /** @test */
    public function cannot_refresh_if_in_beta_assignment()
    {
        $this->actingAs($this->user)
            ->postJson("/api/questions/{$this->question->id}/refresh/{$this->beta_assignment->id}",
                ['update_scores' => false])
            ->assertJson(['message' => 'You cannot refresh this question since this is a Beta assignment. Please contact the Alpha instructor to request the refresh.']);

    }


}
