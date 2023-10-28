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
            "a11y_auto_graded_question_id" => null,
            "answer_html" => null,
            "solution_html" => null,
            "notes" => null,
            "hint" => null,
            "license" => "publicdomain",
            "license_version" => null,
            "qti_prompt" => '',
            'colHeaders' => ["col1", "col2", "col3"],
            'rows' => [
                0 => [
                    'header' => '',
                    'responses' => [
                        0 => [
                            'identifier' => '4fb6d8f8-73c9-4ab0-8ccf-4a95e1df79f7',
                            'correctResponse' => true,
                        ],
                        1 => [
                            'identifier' => '2f43a2b6-2c6e-45c9-9e82-e93cd7388693',
                            'correctResponse' => false,
                        ],
                    ],
                ],
                1 => [
                    'header' => 'r2',
                    'responses' => [
                        0 => [
                            'identifier' => 'b2a3f0a6-f593-41e6-a668-e210dd0cec7f',
                            'correctResponse' => true,
                        ],
                        1 => [
                            'identifier' => 'aca706ec-461b-4316-8f9a-aa6efae6fab4',
                            'correctResponse' => false,
                        ],
                    ],
                ],
            ],
            "qti_json" => '{"questionType":"matrix_multiple_response","prompt":"<p>wefwef</p>\n","colHeaders":["col1","col2","col3"],"rows":[{"header":"r1","responses":[{"identifier":"4fb6d8f8-73c9-4ab0-8ccf-4a95e1df79f7","correctResponse":true},{"identifier":"2f43a2b6-2c6e-45c9-9e82-e93cd7388693","correctResponse":false}]},{"header":"r2","responses":[{"identifier":"b2a3f0a6-f593-41e6-a668-e210dd0cec7f","correctResponse":true},{"identifier":"aca706ec-461b-4316-8f9a-aa6efae6fab4","correctResponse":false}]}],"feedback":{"correct":"<p>all right</p>\n","incorrect":"<p>not all right</p>\n"},"jsonType":"question_json"}'
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
    public function at_least_one_should_be_marked_correct_in_each_column()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();

        $this->assertEquals('You should have at least 1 item checked in this column.', json_decode(json_decode($response)->errors->colHeaders[0])->specific->{"2"});

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
        $this->qti_question_info['colHeaders'][1] = '';
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('Header text is required.', json_decode(json_decode($response)->errors->colHeaders[0])->specific->{"1"});
    }


}
