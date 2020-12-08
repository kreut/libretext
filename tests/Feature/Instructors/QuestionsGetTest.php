<?php

namespace Tests\Feature\Instructors;

use App\Assignment;
use App\Course;
use App\Question;
use App\SubmissionFile;
use App\Submission;
use App\User;
use App\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Carbon\Carbon;

class QuestionsGetTest extends TestCase
{

    /**Still must test the stuff with the correct/completed and number**/
    /** Should test that only an instructor can create an assignment... */
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->user_2->role = 3;
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->question = factory(Question::class)->create();

        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->submission_file = [
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'type' => 'q',
            'user_id' => $this->student_user->id,
            'original_filename' => 'blah blah',
            'submission' => 'sflkjfwlKEKLie.jpg',
            'score'=> '0.00',
            'date_submitted' => Carbon::now()
        ];
        $this->h5pSubmission = [
            'user_id' => $this->student_user->id,
            'technology' => 'h5p',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'score' => '0.00',
            'submission' => '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":11,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}'
        ];


    }

    /** @test */

    public function cannot_update_points_if_not_owner()
    {
        $this->actingAs($this->user_2)->patchJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/update-points",
            ['points' => 10])
            ->assertJson(['message' => 'You are not allowed to update the question points for this assignment.']);
    }
    /** @test **/
    public function non_owner_cannot_get_assignment_info_for_get_questions()
    {

        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/get-questions-info")
            ->assertJson(['type' => 'error',
                'message' => "You are not allowed to get questions for this assignment."]);

    }

    /** @test **/
    public function owner_can_get_assignment_info_for_get_questions()
    {

        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/get-questions-info")
            ->assertJson(['type' => 'success']);

    }


    /** @test **/
    public function cannot_add_a_question_if_students_have_already_made_a_submission()
    {

        Submission::create($this->h5pSubmission);
        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'error',
                'message' => "You can't add a question to this assignment since students have already submitted responses."]);

    }

    /** @test **/
    public function cannot_add_a_question_if_students_have_already_made_a_submission_file()
    {

       SubmissionFile::create($this->submission_file);
        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'error',
                'message' => "You can't add a question to this assignment since students have already submitted responses."]);


    }

    /**@test* */
    public function can_not_visit_get_questions_if_students_have_already_made_a_submission()
    {


    }


    /** @test */

    public function an_instructor_can_upload_a_solution_and_store_it_in_s3()
    {


    }


    /**@test* */
    public function can_not_add_remove_a_question_if_students_have_already_made_a_submission()
    {


    }

    /** @test */

    public function if_page_id_is_included_there_should_be_no_other_tags()
    {
        $this->markTestIncomplete(
            'TODO'
        );


    }



    /** @test */

    public function cannot_update_points_if_points_are_not_valid()
    {
        $this->actingAs($this->user)->patchJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/update-points",
            ['points' => -1])
            ->assertJsonValidationErrors(['points']);

    }

    /** @test */

    public function can_update_points_if_points_if_owner()
    {

        $this->actingAs($this->user)->patchJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/update-points",['points' => 10])
            ->assertJson(['type' => 'success']);


    }

    /** @test */

    public function cannot_update_points_if_student_made_a_submission()
    {
        Submission::create($this->h5pSubmission);
        $this->actingAs($this->user)->patchJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/update-points",['points' => 10])
            ->assertJson(['type' => 'error',
                'message' => "You can't update the question points since students have already submitted responses."]);

    }

    /** @test */

    public function cannot_update_points_if_student_made_a_file_submission()
    {
        Submission::create($this->submission_file);
        $this->actingAs($this->user)->patchJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/update-points",['points' => 10])
            ->assertJson(['type' => 'error',
                'message' => "You can't update the question points since students have already submitted responses."]);

    }


    /**@test */
    public function returns_an_error_with_an_invalid_page_id()
    {

        $this->markTestIncomplete(
            'TODO'
        );


    }

    /**@test */
    public function returns_the_correct_question_given_a_query_page_id()
    {
        $this->markTestIncomplete(
            'TODO'
        );

    }

    /** @test */

    public function user_gets_message_if_there_are_no_questions_associated_with_a_tag()
    {
        $this->markTestIncomplete(
            'TODO'
        );


    }

    /** @test */

    public function user_gets_message_if_there_are_no_questions_associated_with_an_intersection_of_tags()
    {
        $this->markTestIncomplete(
            'TODO'
        );


    }


    /** @test */
    public function can_get_tags_if_not_student()
    {
        $this->actingAs($this->user)->getJson("/api/tags")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_get_tags_if_student()
    {
        $this->actingAs($this->user_2)->getJson("/api/tags")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to retrieve the tags from the database.']);

    }

    /** @test */
    public function can_add_a_question_to_an_assignment_if_you_are_the_owner()
    {
        $this->actingAs($this->user)->postJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'success']);
    }


    /** @test */
    public function cannot_add_a_question_to_an_assignment_if_you_are_not_the_owner()
    {
        $this->actingAs($this->user_2)->postJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to add a question to this assignment.']);
    }

    /** @test */
    public function can_remove_a_question_from_an_assignment_if_you_are_the_owner()
    {
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function cannot_remove_a_question_from_an_assignment_if_there_is_already_a_submission()
    {
        Submission::create($this->h5pSubmission);
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['message' => "You can't remove a question from this assignment since students have already submitted responses."]);
    }

    /** @test */
    public function cannot_remove_a_question_from_an_assignment_if_there_is_already_a_file_submission()
    {
        Submission::create($this->submission_file);
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['message' => "You can't remove a question from this assignment since students have already submitted responses."]);
    }


    /** @test */
    public function cannot_remove_a_question_to_an_assignment_if_you_are_not_the_owner()
    {

        $this->actingAs($this->user_2)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to remove a question from this assignment.']);

    }

    /** @test */
    public function can_get_questions_by_tags()
    {
        $tag = factory(Tag::class)->create(['tag' => 'some tag']);
        $this->question->tags()->attach($tag);
        $this->actingAs($this->user)->postJson("/api/questions/getQuestionsByTags", ['tags' => ['some tag']])
            ->assertJson(['type' => 'success']);


    }

    /** @test */
    public function can_get_assignment_question_ids_if_owner()
    {

        DB::table('assignment_question')
            ->insert([
                'assignment_id' => $this->assignment->id,
                'question_id' => $this->question->id,
                'points' => $this->assignment->default_points_per_question
            ]);

        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}/questions/ids")
            ->assertJson(['type' => 'success',
                'question_ids' => "[{$this->question->id}]"]);

    }

    /** @test */
    public function cannot_get_assignment_question_ids_if_not_owner()
    {
        DB::table('assignment_question')
            ->insert([
                'assignment_id' => $this->assignment->id,
                'question_id' => $this->question->id,
                'points' => $this->assignment->default_points_per_question
            ]);
        $this->actingAs($this->user_2)->getJson("/api/assignments/{$this->assignment->id}/questions/ids")
            ->assertJson(['type' => 'error']);

    }

}
