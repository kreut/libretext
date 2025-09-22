<?php

namespace Tests\Feature\QTI;

use App\SavedQuestionsFolder;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NumericalTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->saved_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id, 'type' => 'my_questions']);
        $this->qti_question_info = ["question_type" => "assessment",
            "folder_id" => $this->saved_questions_folder->id,
            "public" => "0",
            "title" => "some title",
            "author" => "Instructor Kean",
            "tags" => [],
            "technology" => "qti",
            "technology_id" => null,
            "non_technology_text" => null,
            "text_question" => null,
            "a11y_auto_graded_question_id" => null,
            "answer_html" => null,
            "solution_html" => null,
            "notes" => null,
            "hint" => null,
            "license"=>"publicdomain",
            "license_version" => null,
            'open_ended_submission_type' => '0',
            "qti_prompt" => '<p>What is 4+4?</p>',
            'correct_response' => "8",
            'margin_of_error' => "2",
            "qti_json" => '{"prompt":"<p>What is 4+4?</p>","correctResponse":{"value":"8","marginOfError":"2"},"feedback":{"any":"<p>Some other info</p>\n","correct":"<p>general correct</p>","incorrect":"<p>general incorrect</p>"},"questionType":"numerical"}'
        ];
    }

    /** @test * */
    public function cannot_repeat_a_question()
    {
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['type' => 'success']);
        $question_id = DB::table('questions')->first()->id;
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_prompt' => ["This question is identical to the native question with ADAPT ID $question_id."]]]);

    }

    /** @test * */
    public function margin_of_error_must_be_a_positive_number()
    {
        $this->qti_question_info['margin_of_error'] = 'not a number';
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['margin_of_error' => ['The margin of error must be a number.']]]);

        $this->qti_question_info['margin_of_error'] = "-3";
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['margin_of_error' => ['The margin of error must be at least 0.']]]);
    }

    /** @test * */
    public function prompt_must_exist()
    {
        $this->qti_question_info['qti_prompt'] = '';
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJsonValidationErrors('qti_prompt');

    }

    /** @test * */
    public function correct_response_must_be_a_number()
    {
        $this->qti_question_info['correct_response'] = 'not a number';
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['correct_response' => ['The correct response must be a number.']]]);

    }


}
