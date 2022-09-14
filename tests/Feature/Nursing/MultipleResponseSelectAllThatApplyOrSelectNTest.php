<?php

namespace Tests\Feature\Nursing;

use App\SavedQuestionsFolder;
use App\User;
use Tests\TestCase;

class MultipleResponseSelectAllThatApplyOrSelectNTest extends TestCase
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
            'responses' =>
                [
                    0 => [
                        'identifier' => '908ad22c-6625-414c-9811-316f177d6c63',
                        'value' => '',
                        'correctResponse' => true,
                    ],
                    1 => [
                        'identifier' => '27492947-f1c9-4baf-9c11-85ff0365c0b0',
                        'value' => '',
                        'correctResponse' => false,
                    ],
                ],
            "qti_json" => '{ "questionType": "multiple_response_select_all_that_apply", "prompt": "", "responses": [ { "identifier": "27492947-f1c9-4baf-9c11-85ff0365c0b0", "value": "", "correctResponse": false }, { "identifier": "52211c09-e4f5-4c18-a2f3-4eff045c163b", "value": "", "correctResponse": true } ] }'
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
    public function text_is_required()
    {
        $identifier = $this->qti_question_info['responses'][0]['identifier'];
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();

        $this->assertEquals('Text is required.', json_decode(json_decode($response)->errors->responses[0])->specific->{$identifier});
    }

    /** @test */
    public function number_to_select_should_equal_number_of_responses_in_select_n()
    {
        $qti_json = json_decode($this->qti_question_info['qti_json']);
        $qti_json->questionType = 'multiple_response_select_n';
        $qti_json->numberToSelect = 30;
        $this->qti_question_info['qti_json'] = json_encode($qti_json);
        $number_correct_responses = 0;
        foreach (   $this->qti_question_info['responses']  as $response){
            $number_correct_responses += +$response['correctResponse'];

        }
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals("The number of correct responses as determined by the prompt ($qti_json->numberToSelect) is not equal to the number of correct responses ($number_correct_responses).", json_decode(json_decode($response)->errors->responses[0])->general);
    }

}
