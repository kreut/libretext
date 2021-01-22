<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Extension;
use App\Cutup;
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

class QuestionsViewTest extends TestCase
{
    use Statistics;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $course->id, 'solutions_released' => 0]);
        $this->question = factory(Question::class)->create(['page_id' => 1]);
        $this->question_2 = factory(Question::class)->create(['page_id' => 2]);
        $this->question_points = 10;
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => $this->question_points,
            'can_view' => 1,
            'can_submit' => 1,
            'clicker_results_released' => 0,
            'open_ended_submission_type' => 'file'
        ]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => $this->question_points,
            'can_view' => 1,
            'can_submit' => 1,
            'clicker_results_released' => 0,
            'open_ended_submission_type' => 'file'
        ]);;

        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $cutup = factory(Cutup::class)->create(['user_id' => $this->student_user->id, 'assignment_id' => $this->assignment->id]);


        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;

        $this->student_user_3 = factory(User::class)->create();
        $this->student_user_3->role = 3;

        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $course->id
        ]);
        $this->submission_object = '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":11,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}';
        $this->h5pSubmission = [
            'technology' => 'h5p',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission' => $this->submission_object
        ];
    }

    /** @test */
    public function change_incomplete_to_complete_if_completed_all_questions_but_removed_one()
    {

        //give score of incomplete
        $this->assignment->scoring_type = 'c';
        $this->assignment->save();
        Score::create(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'score' => 'i']);
        //submitted the first one
        Submission::create(['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student_user->id,
            'score' => 5,
            'submission_count' => 1,
            'answered_correctly_at_least_once' => false,
            'submission' => $this->submission_object]);
        //remove assessment which they didn't submit anything
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question_2->id}");
        //should give back score of complete
        $new_score = Score::where('user_id', $this->student_user->id)->where('assignment_id', $this->assignment->id)->first()->score;

        $this->assertEquals('c', $new_score);

    }
    /** @test */
    public function complete_should_stay_complete_if_completed_all_questions_but_removed_one()
    {

        //give score of incomplete
        $this->assignment->scoring_type = 'c';
        $this->assignment->save();
        Score::create(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'score' => 'c']);
        //submitted the first one
        Submission::create(['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student_user->id,
            'score' => 5,
            'submission_count' => 1,
            'answered_correctly_at_least_once' => false,
            'submission' => $this->submission_object]);
        //remove assessment which they didn't submit anything
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question_2->id}");
        //should give back score of complete
        $new_score = Score::where('user_id', $this->student_user->id)->where('assignment_id', $this->assignment->id)->first()->score;

        $this->assertEquals('c', $new_score);

        //didn't submit submission for one of them with technology
        ///should return incomplete

        //didn't submit for one of them open ended
        //should return incomplete
    }

    /** @test */
    public function did_not_submit_technology_piece_for_one_of_the_questions_with_technology_should_be_incomplete()
    {

        //give score of incomplete
        $this->assignment->scoring_type = 'c';
        $this->assignment->save();
        Score::create(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'score' => 'c']);
        //submitted the first one
        Submission::create(['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student_user->id,
            'score' => 5,
            'submission_count' => 1,
            'answered_correctly_at_least_once' => false,
            'submission' => $this->submission_object]);
        //remove assessment which they didn't submit anything
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question_2->id}");
        //should give back score of complete
        $new_score = Score::where('user_id', $this->student_user->id)->where('assignment_id', $this->assignment->id)->first()->score;

        $this->assertEquals('c', $new_score);

    }
    /** @test */
    public function correctly_recomputes_assignment_score_of_removed_question_for_points_scoring_type()
    {
        $submission_file_score = 10;
        $submission_score = 20;
        $current_assignment_score = 93;
        SubmissionFile::create(['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student_user->id,
            'type' => 'text',
            'original_filename' => '',
            'score' => $submission_file_score,
            'submission' => 'some text',
            'date_submitted' => Carbon::now()]);
        Submission::create(['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student_user->id,
            'score' => $submission_score,
            'submission_count' => 1,
            'answered_correctly_at_least_once' => false,
            'submission' => $this->submission_object]);
        Score::create(['assignment_id' => $this->assignment->id,
            'user_id' => $this->student_user->id,
            'score' => $current_assignment_score]);

        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'success']);
        $new_score = Score::where('assignment_id', $this->assignment->id)->where('user_id', $this->student_user->id)->first()->score;
        $this->assertEquals($current_assignment_score - $submission_file_score - $submission_score, $new_score);
    }


    /** @test */
    public function student_cannot_submit_text_if_it_was_graded()
    {

        SubmissionFile::create(['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student_user->id,
            'type' => 'text',
            'original_filename' => '',
            'submission' => 'some text',
            'date_submitted' => Carbon::now()]);
        DB::table('submission_files')->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->where('user_id', $this->student_user->id)
            ->update(['date_graded' => Carbon::now()]);

        $this->actingAs($this->student_user)->postJson("/api/submission-texts", [
                'questionId' => $this->question->id,
                'assignmentId' => $this->assignment->id,
                'text_submission' => 'Some other cool text after it was graded.']
        )->assertJson(['message' => 'Your submission has already been graded and may not be re-submitted.']);

    }

    public function student_can_submit_text()
    {
        $this->actingAs($this->student_user)->postJson("/api/submission-texts", [
                'questionId' => $this->question->id,
                'assignmentId' => $this->assignment->id,
                'text_submission' => 'Here is my cool text.']
        )->assertJson(['message' => 'Your text submission was saved.']);
    }


    /** @test */
    public function must_contain_text_when_submitting()
    {

        $this->actingAs($this->student_user)->postJson("/api/submission-texts", [
                'questionId' => $this->question->id,
                'assignmentId' => $this->assignment->id,
                'text_submission' => '']
        )->assertJson(['message' => 'You did not submit any text.']);

    }


    /** @test */
    public function with_a_late_assignment_policy_of_not_accepted_a_student_can_submit_response_if_assignment_past_due_has_extension_even_if_solutions_are_released()
    {
        $this->assignment->due = "2001-03-05 09:00:00";
        $this->assignment->assessment_type = 'delayed';
        $this->assignment->show_scores = false;
        $this->assignment->solutions_released = true;
        $this->assignment->save();

        Extension::create(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'extension' => '2027-01-01 09:00:00']);

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'success']);

    }


    /** @test */

    public function must_submit_a_question_with_a_valid_technology()
    {

        $this->h5pSubmission['technology'] = 'bogus technology';
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)->assertStatus(422);

    }

    /** @test */
    public function correctly_computes_the_z_score_for_a_file_submission()
    {

        $scores = [80, 40, 36];


        //create fake submissions --- I just care about the scores.
        $this->assignment->show_scores = 1;
        $this->assignment->save();

        $this->question->technology = 'h5p';
        $this->question->save();
        $data = [
            'type' => 'q',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission' => 'fake_1.pdf',
            'original_filename' => 'orig_fake_1.pdf',
            'date_submitted' => Carbon::now()];

        $user_ids = [$this->student_user->id, $this->student_user_2->id, $this->student_user_3->id];
        foreach ($user_ids as $key => $user_id) {
            $data['score'] = $scores[$key];
            $data['user_id'] = $user_ids[$key];
            SubmissionFile::create($data);
        }

        $mean = array_sum($scores) / count($scores);
        $std_dev = $this->stats_standard_deviation($scores);
        $z_score = Round(($scores[0] - $mean) / $std_dev, 2);
        //need the token....
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($this->student_user);
        $headers = [
            'Accept' => 'application/json',
            'AUTHORIZATION' => 'Bearer ' . $token
        ];

        $response = $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/questions/view", $headers);
        $this->assertEquals($z_score, $response['questions'][0]['submission_file_z_score']);

    }

    /** @test */
    public function correctly_computes_the_z_score_if_there_is_no_file_submission()
    {

        $scores = [40, 36];

        DB::table('assignment_question')
            ->where('question_id', $this->question->id)
            ->update(['open_ended_submission_type' => 'file']);

        //create fake submissions --- I just care about the scores.
        $this->assignment->show_scores = 1;
        $this->assignment->save();

        $this->question->technology = 'h5p';
        $this->question->save();
        $data = [
            'type' => 'q',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission' => 'fake_1.pdf',
            'original_filename' => 'orig_fake_1.pdf',
            'date_submitted' => Carbon::now()];

        $user_ids = [$this->student_user_2->id, $this->student_user_3->id];
        foreach ($user_ids as $key => $user_id) {
            $data['score'] = $scores[$key];
            $data['user_id'] = $user_ids[$key];
            SubmissionFile::create($data);
        }

        $mean = array_sum($scores) / count($scores);
        $std_dev = $this->stats_standard_deviation($scores);
        $z_score = Round(($scores[0] - $mean) / $std_dev, 2);
        //need the token....
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($this->student_user);
        $headers = [
            'Accept' => 'application/json',
            'AUTHORIZATION' => 'Bearer ' . $token
        ];

        $response = $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/questions/view", $headers);
        $this->assertEquals('N/A', $response['questions'][0]['submission_file_z_score']);

    }

    /** @test */
    public function correctly_computes_the_z_score_for_a_question_submission()
    {
        $scores = [80, 40, 36];

        //create fake submissions --- I just care about the scores.
        $this->assignment->show_scores = 1;
        $this->assignment->save();

        $this->question->technology = 'h5p';
        $this->question->save();
        $data = ['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission_count' => 1,
            'answered_correctly_at_least_once' => false,
            'submission' => $this->submission_object];
        $user_ids = [$this->student_user->id, $this->student_user_2->id, $this->student_user_3->id];
        foreach ($user_ids as $key => $user_id) {
            $data['score'] = $scores[$key];
            $data['user_id'] = $user_ids[$key];
            Submission::create($data);
        }
    }


    /** @test */
    public function correctly_computes_the_z_score_if_there_is_no_question_submission()
    {
        $scores = [40, 36];

        //create fake submissions --- I just care about the scores.
        $this->assignment->show_scores = 1;
        $this->assignment->save();

        $this->question->technology = 'h5p';
        $this->question->save();
        $data = ['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission_count' => 1,
            'answered_correctly_at_least_once' => false,
            'submission' => $this->submission_object];
        $user_ids = [$this->student_user_2->id, $this->student_user_3->id];
        foreach ($user_ids as $key => $user_id) {
            $data['score'] = $scores[$key];
            $data['user_id'] = $user_ids[$key];
            Submission::create($data);
        }

        $mean = array_sum($scores) / count($scores);
        $std_dev = $this->stats_standard_deviation($scores);
        $z_score = Round(($scores[0] - $mean) / $std_dev, 2);
        //need the token....
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($this->student_user);
        $headers = [
            'Accept' => 'application/json',
            'AUTHORIZATION' => 'Bearer ' . $token
        ];

        $response = $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/questions/view", $headers);
        $this->assertEquals('N/A', $response['questions'][0]['submission_z_score']);

    }


    /** @test */

    public function user_can_get_query_page_if_page_id_is_in_one_of_their_assignments()
    {
        $this->actingAs($this->student_user)->getJson("/api/get-locally-saved-page-contents/query/1")
            ->assertJson(['message' => 'authorized']);
    }

    /** @test */

    public function instructor_can_get_query_page_by_page_id()
    {
        $this->actingAs($this->user)->getJson("/api/get-locally-saved-page-contents/query/1")
            ->assertJson(['message' => 'authorized']);
    }


    /** @test */
    public function can_only_submit_once_for_real_time_assessments()
    {
        $this->assignment->assessment_type = 'real time';
        $this->assignment->save();

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['message' => 'You can only submit once since you are provided with real-time feedback.']);


    }


    /** @test */
    public function score_is_correctly_computed_for_a_deduction_with_time_periods_late_policy()
    {

        $this->assignment->late_policy = 'deduction';
        $this->assignment->late_deduction_application_period = '1 hour';
        $this->assignment->late_deduction_percent = 10;
        $now = Carbon::now('UTC');
        $this->assignment->due = $now->subHour(1)->subMinute(2)->toDateTimeString();//was due an hour and 2 minutes ago -- should penalize 20%
        $this->assignment->late_policy_deadline = $now->addHour(5)->toDateTimeString();
        $this->assignment->save();
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission);
        $submission = Submission::where('assignment_id', $this->assignment->id)
            ->where('user_id', $this->student_user->id)
            ->where('question_id', $this->question->id)
            ->first();
//2 periods, therefore the 2....
        $this->assertEquals($submission->score, $this->question_points * (1 - 2 * $this->assignment->late_deduction_percent / 100));

    }

    /** @test */

    public function score_is_correctly_computed_for_a_deduction_only_once_late_policy()
    {

        $this->assignment->late_policy = 'deduction';
        $this->assignment->late_deduction_application_period = 'once';
        $this->assignment->late_deduction_percent = 50;
        $now = Carbon::now('UTC');
        $this->assignment->due = $now->subHour(1)->toDateTimeString();//was due an hour ago.
        $this->assignment->late_policy_deadline = $now->addHour(1)->toDateTimeString();
        $this->assignment->save();

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission);
        $submission = Submission::where('assignment_id', $this->assignment->id)
            ->where('user_id', $this->student_user->id)
            ->where('question_id', $this->question->id)
            ->first();

        $this->assertEquals($submission->score, $this->question_points * $this->assignment->late_deduction_percent / 100);

    }


    /** @test */
    public function real_time_solutions_can_only_be_downloaded_after_initial_submission()
    {


    }


    /** @test */

    public function late_question_submission_marked_late_for_marked_late_late_policy()
    {
//todo

    }

    /** @test */

    public function late_file_submission_marked_late_for_marked_late_late_policy()
    {
//todo

    }


    /** @test */
    public function not_accepted_late_policy_will_not_accept_late_submissions()
    {
        $this->assignment->due = "2001-03-05 09:00:00";
        $this->assignment->save();


        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['message' => 'No responses will be saved since the due date for this assignment has passed.']);

    }

    /** @test */
    public function deduction_or_marked_late_policy_will_accept_past_the_due_date_and_before_the_late_policy_deadline()
    {
        $this->assignment->due = "2020-12-10 09:00:00";
        $this->assignment->late_policy = 'marked late';
        $this->assignment->late_policy_deadline = "2021-03-05 09:00:00";
        $this->assignment->save();

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['message' => 'Question submission saved. Your scored was updated.']);

        $this->assignment->late_policy = 'delayed';
        $this->assignment->save();
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['message' => 'Question submission saved. Your scored was updated.']);

    }

    /** @test */
    public function deduction_or_marked_late_policy_will_not_accept_past_the_due_date_and_after_the_late_policy_deadline()
    {
        $this->assignment->due = "2020-12-10 09:00:00";
        $this->assignment->late_policy = 'marked late';
        $this->assignment->late_policy_deadline = "2020-12-11 09:00:00";
        $this->assignment->save();

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['message' => 'No more late responses are being accepted.']);

        $this->assignment->late_policy = 'deduction';
        $this->assignment->save();
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['message' => 'No more late responses are being accepted.']);

    }


    /** @test */
    public function with_a_late_assignment_policy_of_not_accepted_a_student_can_submit_response_if_assignment_past_due_has_extension()
    {
        $this->assignment->due = "2001-03-05 09:00:00";
        $this->assignment->save();

        Extension::create(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'extension' => '2027-01-01 09:00:00']);

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'success']);

    }


    /** @test */
    public function learning_tree_do_not_allow_submissions_if_solutions_released()
    {
        $this->assignment->assessment_type = 'learning tree';
        $this->assignment->show_scores = false;
        $this->assignment->solutions_released = true;
        $this->assignment->save();

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since the solutions to this assignment have been released.']);

    }

    /** @test */
    public function delayed_do_not_allow_submissions_if_scores_are_shown_or_solutions_released()
    {

        $this->assignment->assessment_type = 'delayed';
        $this->assignment->show_scores = false;
        $this->assignment->solutions_released = true;
        $this->assignment->save();

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since the solutions to this assignment have been released.']);

    }

    /** @test */
    public function can_submit_response()
    {

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'success']);

    }


    /** @test */

    public function student_cannot_create_cutups_if_the_assignment_is_past_due()
    {
        $this->createSubmissionFile();
        $this->assignment->due = Carbon::yesterday();
        $this->assignment->save();
        $this->actingAs($this->student_user)->postJson("/api/cutups/{$this->assignment->id}/{$this->question->id}/set-as-solution-or-submission")
            ->assertJson(['message' => "No responses will be saved since the due date for this assignment has passed."]);

    }


    /** @test */
    public function with_a_late_assignment_policy_of_not_accepted_a_student_cannot_submit_response_if_assignment_past_due_and_no_extension()
    {
        $this->assignment->due = "2001-03-05 09:00:00";
        $this->assignment->save();

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'error', 'message' => 'No responses will be saved since the due date for this assignment has passed.']);

    }

    /** @test */
    public function with_a_late_assignment_policy_of_not_accepted_a_student_cannot_submit_response_if_assignment_past_due_and_past_extension()
    {
        $this->assignment->due = "2001-03-05 09:00:00";
        $this->assignment->save();

        Extension::create(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'extension' => '2020-01-01 09:00:00']);

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since your extension for this assignment has passed.']);

    }


    public function assignments_of_scoring_type_c_will_count_the_number_of_submissions_and_compare_to_the_number_of_questions()
    {
        $this->assignment->scoring_type = 'c';
        $this->assignment->save();

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission);

        $score = DB::table('scores')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->first();
        $this->assertEquals(null, $score, 'No assignment score saved in not completed assignment.');


        $this->actingAs($this->student_user)->postJson("/api/submissions", [
            'technology' => 'h5p',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'submission' => $this->h5pSubmission['submission']])
            ->assertJson(['type' => 'success']);

        $score = DB::table('scores')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->get()
            ->pluck('score');
        $this->assertEquals('c', $score[0], 'Assignment marked as completed when all questions are answered.');

    }

    /** @test */

    public function user_cannot_get_query_page_if_page_id_is_not_in_one_of_their_assignments()
    {
        $this->actingAs($this->student_user)->getJson("/api/get-locally-saved-page-contents/query/10")
            ->assertJson(['message' => 'You are not allowed to view this non-technology question.']);
    }


    /** @test */
    public function can_get_assignment_title_if_owner_course()
    {
        $response['assignment']['name'] = $this->assignment->name;
        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/view-questions-info")
            ->assertJson($response);
    }

    /** @test */
    public function can_get_assignment_title_if_student_in_course()
    {
        $response['assignment']['name'] = $this->assignment->name;
        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/view-questions-info")
            ->assertJson($response);
    }

    /** @test */
    public function cannot_get_assignment_title_if_not_student_in_course()
    {
        $this->actingAs($this->student_user_2)->getJson("/api/assignments/{$this->assignment->id}/view-questions-info")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to access this assignment.']);
    }


    /** @test */

    public function student_view_scores_info_if_enrolled_in_the_course()
    {
        $score = 10;
        Score::create(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'score' => $score]);
        $response['assignment']['scores'] = [$score];
        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/view-questions-info")
            ->assertJson($response);
    }

    /** @test */

    public function student_can_view_questions_info()
    {
        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/view-questions-info")
            ->assertJson(['type' => 'success']);
    }

    /** @test */

    public function student_cannot_view_questions_info_if_not_enrolled_in_the_course()
    {
        $this->actingAs($this->student_user_2)->getJson("/api/assignments/{$this->assignment->id}/view-questions-info")
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to access this assignment.']);
    }

    /** @test */

    public function owner_can_view_questions_info_if_owner_of_the_course()
    {
        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/view-questions-info")
            ->assertJson(['type' => 'success']);
    }


    /** @test */

    public function student_cannot_get_scores_by_assignment_and_question()
    {
        $this->actingAs($this->student_user)->getJson("/api/scores/summary/{$this->assignment->id}/{$this->question->id}")
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to retrieve this summary.']);
    }

    /** @test */

    public function owner_can_get_scores_by_assignment_and_question()
    {
        factory(SubmissionFile::class)->create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'type' => 'q',
            'original_filename' => 'some original name.pdf',
            'score' => 4]);

        $this->h5pSubmission = [
            'technology' => 'h5p',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission' => '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":11,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}',
        ];//gives them 10 points for the question since they got it correct

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission);

        $response['scores'] = ["14.00"];
        $this->actingAs($this->user)->getJson("/api/scores/summary/{$this->assignment->id}/{$this->question->id}")
            ->assertJson($response);

    }

    /** @test */
    public function if_there_are_no_scores_it_returns_an_empty_array()
    {

        $this->actingAs($this->user)->getJson("/api/scores/summary/{$this->assignment->id}/{$this->question->id}")
            ->assertJson([]);

    }


    /** @test */

    public function students_cannot_email_users_if_the_user_did_not_grade_their_question()
    {
        $this->actingAs($this->student_user_2)->postJson('/api/email/send', [
            'name' => 'Ima Student',
            'email' => 'some@email.com',
            'subject' => 'Grading issue',
            'text' => 'some student complaint',
            'type' => 'contact_grader',
            'extraParams' => ['question_id' => $this->question->id, 'assignment_id' => $this->assignment->id],
            'to_user_id' => 100000,
        ])
            ->assertJson(['type' => 'error', 'message' => 'You are not allowed to send that person an email.']);

    }

    /** @test */

    public function students_can_email_users_if_the_user_graded_their_question()
    {


    }

    /** @test */

    public function anyone_can_contact_us()
    {


    }

    /** @test */
    public function can_get_assignment_questions_if_student_in_course()
    {

        //needed because the token wasn't being passed through
        //https://laracasts.com/discuss/channels/testing/laravel-testcase-not-sending-authorization-headers
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($this->student_user);
        $headers = [
            'Accept' => 'application/json',
            'AUTHORIZATION' => 'Bearer ' . $token
        ];
        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/questions/view", $headers)
            ->assertJson(['type' => 'success']);

    }

    public function createSubmissionFile()
    {
        //set up this way since I wouldn't have been able to remove questions below if there was already a submission
        factory(SubmissionFile::class)->create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'type' => 'a',
            'original_filename' => 'some original name.pdf']);

    }

    /** @test */

    public function student_cannot_create_cutups_for_a_question_not_in_their_assignment()
    {
        factory(Question::class)->create(['id' => 10000000, 'page_id' => 100000000]);
        $this->actingAs($this->student_user)->postJson("/api/cutups/{$this->assignment->id}/10000000/set-as-solution-or-submission")
            ->assertJson(['message' => "That question is not in the assignment."]);


    }

    /** @test */

    public function expect_a_comma_separated_list_of_cutups()
    {
        $this->createSubmissionFile();
        $this->actingAs($this->student_user)->postJson("/api/cutups/{$this->assignment->id}/{$this->question->id}/set-as-solution-or-submission",
            ['chosen_cutups' => '1:2,aaa'])
            ->assertJson(['message' => "Your cutups should be a comma separated list of pages chosen from your original PDF."]);

    }

    /** @test */

    public function a_student_can_create_cutups_from_a_comma_separated_list()
    {

    }


    /** @test */

    public function student_can_create_cutups_if_the_assignment_is_past_due_but_the_extension_has_not_past()
    {

        //Need to mock out the uploaded file

        //$this->createSubmissionFile();
        // $this->assignment->due = Carbon::yesterday();
        // $this->assignment->save();
        // factory(Extension::class)->create(['user_id' => $this->student_user->id, 'assignment_id' => $this->assignment->id]);
        // $this->actingAs($this->student_user)->postJson("/api/cutups/{$this->assignment->id}/{$this->question->id}/set-as-solution-or-submission")
        //     ->assertJson(['message' => "Your cutup has been saved as your file submission for this question."]);

    }

    /** @test */

    public function instructor_cannot_create_cutups_if_they_are_not_the_owner_of_the_course()
    {
        $this->actingAs($this->user_2)->postJson("/api/cutups/{$this->assignment->id}/{$this->question->id}/set-as-solution-or-submission")
            ->assertJson(['message' => "You are not allowed to create a cutup for this assignment."]);


    }

    /** @test */

    public function instructor_can_create_cutups_if_they_are_the_owner()
    {
        //need to mock out the file
        // $this->actingAs($this->user)->postJson("/api/cutups/{$this->assignment->id}/{$this->question->id}/set-as-solution-or-submission")
        //    ->assertJson(['message' => "Your cutup has been set as the solution."]);
    }

    /** @test */

    public function one_cannot_add_a_question_to_an_assignment_if_a_student_has_submitted_a_response()
    {


    }


    /** @test */

    public function a_student_cannot_download_a_solution_to_a_question_if_the_solutions_are_not_released()
    {
        $this->actingAs($this->student_user)->postJson('/api/solution-files/download', [
            'level' => 'q',
            'question_id' => $this->question->id,
            'assignment_id' => $this->assignment->id])
            ->assertJson(['message' => "The solutions are not released so you can't download the solution."]);


    }


    /** @test */

    public function a_student_cannot_download_a_solution_to_a_question_in_an_assignment_that_is_not_in_an_enrolled_course()
    {
        $this->assignment->solutions_released = 1;
        $this->assignment->save();
        $this->actingAs($this->student_user_2)->postJson('/api/solution-files/download', [
            'level' => 'q',
            'question_id' => $this->question->id,
            'assignment_id' => $this->assignment->id])
            ->assertJson(['message' => 'You are not allowed to download these solutions.']);


    }

    /** @test */

    public function a_student_can_download_a_solution_uploaded_by_their_instructor()
    {


    }


    /** @test */

    public function a_non_instructor_cannot_upload_a_solution()
    {
        $this->actingAs($this->student_user)->putJson("/api/solution-files", [
            'question_id' => 1])
            ->assertJson(['message' => 'You are not allowed to upload solutions.']);


    }

    /** @test */

    public function an_instructor_can_upload_a_solution()
    {


    }

    /** @test */

    public function you_cannot_download_a_solution_that_is_not_part_of_an_assignment()
    {
        $this->actingAs($this->user)->postJson('/api/solution-files/download', [
            'level' => 'q',
            'question_id' => 1000,
            'assignment_id' => $this->assignment->id])
            ->assertJson(['message' => 'That question is not part of the assignment so you cannot download the solutions.']);

    }


    /** @test */
    public function must_submit_a_question_with_a_valid_assignment_number()
    {

        $this->h5pSubmission['assignment_id'] = false;
        $this->assignment->save();
        $this->actingAs($this->student_user)->postJson("/api/submissions",
            $this->h5pSubmission)->assertStatus(422);

    }

    /** @test */
    public function must_submit_a_question_with_a_valid_question_number()
    {


        $this->h5pSubmission['question_id'] = false;
        $this->actingAs($this->student_user)->postJson("/api/submissions",
            $this->h5pSubmission)->assertStatus(422);

    }


    /** @test */

    public function assignments_of_scoring_type_p_and_no_question_files_will_compute_the_score_based_on_the_question_points()
    {

        $this->actingAs($this->student_user)->postJson("/api/submissions",
            $this->h5pSubmission);


        $score = DB::table('scores')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->get()
            ->pluck('score');
        $points_1 = DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->get()
            ->pluck('points');


        $this->assertEquals(number_format($points_1[0], 2), number_format($score[0], 2), 'Score saved when student submits.');

        //do it again and it should update

        $this->actingAs($this->student_user)->postJson("/api/submissions", [
                'technology' => 'h5p',
                'assignment_id' => $this->assignment->id,
                'question_id' => $this->question_2->id,
                'submission' => $this->h5pSubmission['submission']]
        );

        $points_2 = DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question_2->id)
            ->get()
            ->pluck('points');

        $score = DB::table('scores')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->get()
            ->pluck('score');

        $this->assertEquals(number_format($points_1[0] + $points_2[0], 2), number_format($score[0], 2), 'Score saved when student submits.');


    }

    /**@test* */

    public function score_is_computed_correctly_for_h5p()
    {

    }

    /** @test */

    public function score_is_computed_correctly_for_imathas()
    {

    }

    /**@test* */

    public function score_is_computed_correctly_for_webwork()
    {

    }

    /**@test* */

    public function the_associated_technology_is_valid()
    {


    }

    /**@test* */

    public function the_assignment_id_is_an_integer()
    {


    }

    /**@test* */

    public function the_question_id_is_an_integer()
    {


    }

    /**@test* */
    public function can_not_update_question_points_if_students_have_already_made_a_submission()
    {

//not sure if this is even a real thing: I have an update in the controller but nothing in the questions.get.vue?
    }


    /**@test* */

    public function the_submission_is_a_string()
    {


    }

    /** @test */


    /** @test */
    public function cannot_store_a_file_if_the_number_of_uploads_exceeds_the_max_number_of_uploads()
    {

    }

    /** @test */
    public function cannot_store_a_file_if_the_size_of_the_file_exceeds_the_max_size_permitted()
    {

    }

    /** @test */

    public function cannot_store_a_question_file_if_it_is_not_in_the_assignment()
    {


    }

    /** @test */

    public function cannot_store_a_question_file_if_it_has_the_wrong_type()
    {
//testing for question/assignment

    }

    /** @test */

    public function cannot_store_a_question_file()
    {


    }

    /** @test */

    public function can_toggle_question_files_if_you_are_the_owner()
    {


    }

    /** @test */

    public function cannot_toggle_question_files_if_you_are_not_the_owner()
    {


    }

    /** @test */

    public function will_mark_assignment_as_completed_if_number_of_questions_is_equal_to_number_of_questions()
    {


    }


    /** @test */
    public function can_update_response()
    {

        ///to do ---- change the second one to see if the database actually updated!
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission);


        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_submit_response_if_question_not_in_assignment()
    {
        $this->actingAs($this->student_user)->postJson("/api/submissions", [
            'assignment_id' => $this->assignment->id,
            'question_id' => 0,
            'submission' => 'some submission'])
            ->assertJson(['type' => 'error',
                'message' => 'That question is not part of the assignment.']);
    }

    /** @test */
    public function cannot_submit_response_if_user_not_enrolled_in_course()
    {
        $this->actingAs($this->student_user_2)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since the assignment is not part of your course.']);

    }


    /** @test */
    public function cannot_submit_response_if_assignment_not_yet_available()
    {
        $this->assignment->available_from = "2035-03-05 09:00:00";
        $this->assignment->save();


        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since this assignment is not yet available.']);

    }

    /** @test */
    public function can_get_titles_of_learning_tree()
    {
        $this->actingAs($this->user)->getJson("/api/libreverse/library/chem/page/21691/title")
            ->assertSeeText('Studying Chemistry');


    }


    /** @test */
    public function cannot_get_assignment_questions_if_not_student_in_course()
    {
        $this->actingAs($this->student_user_2)->getJson("/api/assignments/{$this->assignment->id}/questions/view")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to access this assignment.']);

    }

    /** @test */
    public function can_remove_question_from_assignment_if_owner()
    {
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_remove_question_from_assignment_if_not_owner()
    {
        $this->actingAs($this->user_2)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to remove a question from this assignment.']);
    }

    /** @test */
    public function can_view_page_if_grader_in_course()
    {


    }

}
