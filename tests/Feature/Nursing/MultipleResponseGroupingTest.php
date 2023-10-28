<?php

namespace Tests\Feature\Nursing;

use App\SavedQuestionsFolder;
use App\User;
use Tests\TestCase;

class MultipleResponseGroupingTest extends TestCase
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
            'headers' => ["", "", ""],
            'rows' =>
                [
                    0 => [
                        'grouping' => '',
                        'responses' => [
                            0 => [
                                'identifier' => '135988c2-275d-40ca-a794-6f8b0ed12c48',
                                'value' => '',
                                'correctResponse' => false,
                            ],
                        ],
                    ],
                    1 => [
                        'grouping' => '',
                        'responses' => [
                            0 => [
                                'identifier' => 'e6a68185-3a42-4962-ad15-a40692d571d3',
                                'value' => '',
                                'correctResponse' => false,
                            ],
                        ],
                    ]
                ],
            "qti_json" => '{ "questionType": "multiple_response_grouping", "prompt": "", "headers": [ "", "" ], "rows": [ { "grouping": "", "responses": [ { "identifier": "135988c2-275d-40ca-a794-6f8b0ed12c48", "value": "", "correctResponse": false } ] }, { "grouping": "", "responses": [ { "identifier": "e6a68185-3a42-4962-ad15-a40692d571d3", "value": "", "correctResponse": false } ] } ] }'
        ];
    }

    /** @test */
    public function cannot_repeat_within_a_grouping()
    {

        $this->qti_question_info['rows'] = [
            0 => [
                'grouping' => '',
                'responses' => [
                    0 => [
                        'identifier' => '135988c2-275d-40ca-a794-6f8b0ed12c48',
                        'value' => 'some response',
                        'correctResponse' => false,
                    ],
                    1 => [
                        'identifier' => 'e6a68185-3a42-4962-ad15-a40692d571d3',
                        'value' => 'some response',
                        'correctResponse' => false,
                    ],
                ],
            ]
        ];

        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('The response `some response` appears multiple times within the grouping.', json_decode(json_decode($response)->errors->rows[0])->specific->{0}->value->{1});
    }

    /** @test */
    public function headers_are_required()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('Header text is required.', json_decode(json_decode($response)->errors->headers[0])->specific[0]);
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
        $this->assertEquals('Header text is required.', json_decode(json_decode($response)->errors->headers[0])->specific[0]);

    }

    /** @test */
    public function responses_need_text()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('Text is required.', $this->getRowsResponse($response)->value[0]);

    }

    /** @test */
    public function at_least_one_in_the_group_should_be_correct()
    {
        $this->qti_question_info['rows'] = [
            0 => [
                'grouping' => '',
                'responses' => [
                    0 => [
                        'identifier' => '135988c2-275d-40ca-a794-6f8b0ed12c48',
                        'value' => 'some response',
                        'correctResponse' => false,
                    ],
                    1 => [
                        'identifier' => 'e6a68185-3a42-4962-ad15-a40692d571d3',
                        'value' => 'some other response',
                        'correctResponse' => false,
                    ],
                ],
            ]
        ];
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('At least one in the group must be marked correct.', $this->getRowsResponse($response)->at_least_one_correct);

    }

    /** @test */
    public function each_group_should_have_at_least_two_responses()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('There should be at least two responses.', $this->getRowsResponse($response)->at_least_two_responses);

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

    public function getRowsResponse($response)
    {
        return json_decode(json_decode($response)->errors->rows[0])->specific->{0};
    }
}
