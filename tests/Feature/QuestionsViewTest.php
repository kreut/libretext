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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionsViewTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id, 'solutions_released' => 0]);
        $this->question = factory(Question::class)->create(['page_id' => 1]);
        $this->question_2 = factory(Question::class)->create(['page_id' => 2]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10
        ]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => 10
        ]);;

        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->cutup = factory(Cutup::class)->create(['user_id' => $this->student_user->id, 'assignment_id' => $this->assignment->id]);


        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;

        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);
        $this->h5pSubmission = [
            'technology' => 'h5p',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission' => '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":11,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}'
        ];


    }

    /** @test */

    public function user_cannot_get_query_page_if_page_id_is_not_in_one_of_their_assignments(){
       $this->actingAs($this->student_user)->getJson("/api/get-locally-saved-query-page-contents/10")
            ->assertJson(['message' => 'You are not allowed to view this non-technology question.']);
    }

    /** @test */

    public function user_can_get_query_page_if_page_id_is_in_one_of_their_assignments(){
        $this->actingAs($this->student_user)->getJson("/api/get-locally-saved-query-page-contents/1")
            ->assertJson(['message' => 'authorized']);
    }

    /** @test */

    public function instructor_can_get_query_page_by_page_id(){
        $this->actingAs($this->user)->getJson("/api/get-locally-saved-query-page-contents/1")
            ->assertJson(['message' => 'authorized']);
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
            'assignment_id'=> $this->assignment->id,
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

    public function student_cannot_create_cutups_if_the_assignment_is_past_due()
    {
        $this->createSubmissionFile();
        $this->assignment->due = Carbon::yesterday();
        $this->assignment->save();
        $this->actingAs($this->student_user)->postJson("/api/cutups/{$this->assignment->id}/{$this->question->id}/set-as-solution-or-submission")
            ->assertJson(['message' => "You cannot set this cutup as a solution since this assignment is past due."]);

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

    public function must_submit_a_question_with_a_valid_technology()
    {
        $this->assignment->submission_files = '0';
        $this->assignment->save();
        $this->h5pSubmission['technology'] = 'bogus technology';
        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)->assertStatus(422);

    }

    /** @test */
    public function must_submit_a_question_with_a_valid_assignment_number()
    {
        $this->assignment->submission_files = '0';
        $this->h5pSubmission['assignment_id'] = false;
        $this->assignment->save();
        $this->actingAs($this->student_user)->postJson("/api/submissions",
            $this->h5pSubmission)->assertStatus(422);

    }

    /** @test */
    public function must_submit_a_question_with_a_valid_question_number()
    {
        $this->assignment->submission_files = '0';
        $this->assignment->save();
        $this->h5pSubmission['question_id'] = false;
        $this->actingAs($this->student_user)->postJson("/api/submissions",
            $this->h5pSubmission)->assertStatus(422);

    }


    /** @test */

    public function assignments_of_scoring_type_p_and_no_question_files_will_compute_the_score_based_on_the_question_points()
    {
        $this->assignment->submission_files = '0';
        $this->assignment->save();
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
    public function can_submit_response()
    {

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'success']);

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
    public function can_submit_response_if_assignment_past_due_has_extension()
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
    public function cannot_submit_response_if_assignment_past_due_and_no_extension()
    {
        $this->assignment->due = "2001-03-05 09:00:00";
        $this->assignment->save();

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'error', 'message' => 'No responses will be saved since the due date for this assignment has passed.']);

    }

    /** @test */
    public function cannot_submit_response_if_assignment_past_due_and_past_extension()
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
