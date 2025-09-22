<?php

namespace Tests\Feature\QTI;

use App\SavedQuestionsFolder;
use App\Traits\Test;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use function factory;

class SimpleChoiceTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->saved_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id, 'type' => 'my_questions']);
        $this->directory = 'test directory';
        $this->filename = 'some filename';


        $this->qti_question_info = ["question_type" => "assessment",
            "folder_id" => $this->saved_questions_folder->id,
            "public" => "0",
            "title" => "some title",
            "author" => "Instructor Kean",
            "tags" => [],
            "technology" => "qti",
            "technology_id" => null,
            "non_technology_text" => null,
            'open_ended_submission_type' => '0',
            "text_question" => null,
            "a11y_auto_graded_question_id" => null,
            "answer_html" => null,
            "solution_html" => null,
            "notes" => null,
            "hint" => null,
            "license" => "publicdomain",
            "license_version" => null,
            "qti_randomize_order" => 'yes',
            "qti_prompt" => "<p>Some prompt</p>",
            "qti_correct_response" => "adapt-qti-2",
            "qti_simple_choice_0" => "some response",
            "qti_simple_choice_1" => "some other response",
            "qti_json" => '{"prompt":"<p>Some prompt</p>","simpleChoice":[{"identifier":"5416","value":"Better answer","correctResponse":true},{"identifier":"2455","value":"cos(x)","correctResponse":false}],"questionType":"multiple_choice"}'
        ];
        $this->qti_job_id = DB::table('qti_jobs')->insertGetId([
            'user_id' => $this->user->id,
            'qti_source' => 'v2.2',
            'public' => 1,
            'folder_id' => factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id])->id,
            'license' => 'Public domain',
            'qti_directory' => $this->directory]);
    }

    /** @test * */
    public function simpleChoice_question_can_be_edited_without_repeat_issue()
    {

        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['type' => 'success']);
        $question_id = DB::table('questions')->where('qti_json', $this->qti_question_info['qti_json'])->first()->id;
        $this->qti_question_info['id'] = $question_id;
        DB::table('questions')->count();
        $this->qti_question_info['hint'] = 'sdfdsfsdf';
        $this->qti_question_info['revision_action'] = 'notify';
        $this->qti_question_info['reason_for_edit'] = 'blah blah';
        $this->qti_question_info['automatically_update_revision'] = false;
        $this->qti_question_info = $this->addQuestionRevisionInfo($this->qti_question_info);

        $this->actingAs($this->user)->patchJson("/api/questions/$question_id",
            $this->qti_question_info)
            ->assertJson(['type' => 'success']);
    }

    /** @test * */
    public function simpleChoice_question_cannot_be_repeated()
    {

        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['type' => 'success']);
        $question = DB::table('questions')->orderBy('id', 'desc')->first();
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_prompt' => [
                "This question is identical to the native question with ADAPT ID $question->id."
            ]
            ]]);
    }


    /** @test */
    public function qti_randomize_order_is_required()
    {
        unset($this->qti_question_info['qti_randomize_order']);
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_randomize_order' => [
                "The qti randomize order field is required."
            ]
            ]]);
    }

    /** @test */
    public function there_should_be_at_least_two_choices()
    {
        unset($this->qti_question_info['qti_simple_choice_1']);
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_simple_choice_0' => [
                "You should have at least 2 responses."
            ]
            ]]);
    }

    /** @test */
    public function two_choices_should_not_be_the_same()
    {
        $this->qti_question_info = str_replace('some other response', 'some response', $this->qti_question_info);

        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_simple_choice_0' => [
                "The response 'some response' appears more than once."
            ]
            ]]);
    }


    /** @test * */
    public function prompt_is_required()
    {
        unset($this->qti_question_info['qti_prompt']);
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJsonValidationErrors('qti_prompt');
    }

    /** @test * */
    public function prompt_should_have_length()
    {
        $this->qti_question_info['qti_prompt'] = '';
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => [
                'qti_prompt' => [
                    "A prompt is required."
                ]
            ]
            ]);

    }

    /** @test * */
    public function correct_response_is_required()
    {
        unset($this->qti_question_info['qti_correct_response']);
        $this->qti_question_info['qti_json'] = str_replace('"correctResponse":true', '"correctResponse":false', $this->qti_question_info['qti_json']);
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => [
                'qti_simple_choice_0' => [
                    "You didn't select any of the responses as being correct."
                ]
            ]
            ]);
    }


}
