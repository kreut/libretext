<?php

namespace Tests\Feature\Nursing;

use App\SavedQuestionsFolder;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DropDownTableTest extends TestCase
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
            "license" => "publicdomain",
            "license_version" => null,
            "qti_prompt" => '',
            'colHeaders' => ["", ""],
            'rows' => [[
                'header' => '',
                'selected' => NULL,
                'responses' => [
                    0 => [
                        'identifier' => 'de7a40d1-e5df-4977-970e-209233f2aad7',
                        'value' => '',
                        'correctResponse' => true,
                    ],
                ],
            ]],
            "qti_json" => '{ "questionType": "drop_down_table", "prompt": "", "colHeaders": [ "", "" ], "rows": [ { "header": "", "selected": null, "responses": [ { "identifier": "de7a40d1-e5df-4977-970e-209233f2aad7", "value": "", "correctResponse": true } ] } ] }'
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

        $this->assertEquals('Header text is required.', json_decode(json_decode($response)->errors->colHeaders[0])->specific[0]);
    }

    /** @test */
    public function at_least_two_rows()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('You need at least 1 Distractor.', $this->getRowResponse($response)->general);
    }

    /** @test */
    public function row_headers_are_required()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals("Row header is required.", $this->getRowResponse($response)->specific[0]->header);
    }


    /** @test */
    public function each_identifier_needs_text()
    {
        $identifier = 'de7a40d1-e5df-4977-970e-209233f2aad7';
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('Text is required.', $this->getRowResponse($response)->specific[0]->{$identifier});
    }

    public function getRowResponse($response)
    {
        return json_decode(json_decode($response)->errors->rows[0]);
    }
}


