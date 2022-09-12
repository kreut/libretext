<?php

namespace Tests\Feature\Nursing;

use App\SavedQuestionsFolder;
use App\User;
use Tests\TestCase;

class MatrixMultipleResponseTest extends TestCase
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
            'rows' =>  [[ "", false, false ], [ "", false, false ] ],
            "qti_json" => '{ "questionType": "matrix_multiple_response", "prompt": "", "headers": [ "", "", "" ], "rows": [ [ "", false, false ], [ "", false, false ] ] }'
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
    public function row_headers_are_required()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();

        $this->assertEquals('Row header is required.', json_decode(json_decode($response)->errors->rows[0])[0]->header);

    }

    /** @test */
    public function at_least_one_should_be_marked_correct()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('At least one should be marked correct.', json_decode(json_decode($response)->errors->rows[0])[0]->at_least_one_marked_correct);

    }


    /** @test */
    public function there_should_be_at_least_one_row()
    {
        $this->qti_question_info['rows'] = [];
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals("The rows field is required.", json_decode($response)->errors->rows[0]);
    }

    /** @test */
    public function headers_are_required()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('Header text is required.',json_decode(json_decode($response)->errors->headers[0])->specific[0]);
    }


}
