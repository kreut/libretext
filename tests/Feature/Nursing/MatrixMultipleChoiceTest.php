<?php

namespace Tests\Feature\Nursing;

use App\SavedQuestionsFolder;
use App\User;
use Tests\TestCase;

class MatrixMultipleChoiceTest extends TestCase
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
            "a11y_technology" => null,
            "a11y_technology_id" => null,
            "answer_html" => null,
            "solution_html" => null,
            "notes" => null,
            "hint" => null,
            "license" => "publicdomain",
            "license_version" => null,
            "qti_prompt" => '',
            'headers' => ["", "", ""],
            'rows' => [['label' => "", 'correctResponse' => ""]],
            'correct_response' => "8",
            'margin_of_error' => "2",
            "qti_json" => '{ "questionType": "matrix_multiple_choice", "prompt": "", "headers": [ "", "", "" ], "rows": [ { "label": "", "correctResponse": "" } ] }'
        ];
    }

    /** @test */
    public function prompt_is_required()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('A prompt is required.', json_decode($response)->errors->qti_prompt[0]);
    }

    /** @test */
    public function headers_are_required()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertTrue(strpos(json_decode($response)->errors->headers[0], 'Header text is required.') !== false);
    }

    /** @test */
    public function each_row_needs_correct_response()
    {

        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertTrue(strpos(json_decode($response)->errors->rows[0], 'Correct response is required.') !== false);
    }

    /** @test */
    public function each_row_needs_a_label()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertTrue(strpos(json_decode($response)->errors->rows[0], 'Row header is required.') !== false);
    }


}
