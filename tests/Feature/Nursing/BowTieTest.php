<?php

namespace Tests\Feature\Nursing;

use App\SavedQuestionsFolder;
use App\User;
use Tests\TestCase;

class BowTieTest extends TestCase
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
            'actions_to_take' => [
                0 => [
                    'identifier' => '6c9b7f49-b8f3-4d1b-ab8a-390aa2497799',
                    'value' => '',
                    'correctResponse' => true,
                ],
                1 => [
                    'identifier' => '854c1411-c0ec-4e6f-a0cc-2d1a66746336',
                    'value' => '',
                    'correctResponse' => true,
                ],
            ],
            'potential_conditions' => [
                0 => [
                    'identifier' => '31909ed9-7a36-4018-bb1f-bebc20d426f4',
                    'value' => '',
                    'correctResponse' => true,
                ],
            ],
            'parameters_to_monitor' => [
                0 => [
                    'identifier' => '34d0f848-0277-44e2-bcf9-95c259394e6b',
                    'value' => '',
                    'correctResponse' => true,
                ],
                1 => [
                    'identifier' => '50d4bf3b-6cca-4a71-a372-c06f5120cf12',
                    'value' => '',
                    'correctResponse' => true,
                ],
            ],
            "qti_json" => '{ "questionType": "bow_tie", "actionsToTake": [ { "identifier": "6c9b7f49-b8f3-4d1b-ab8a-390aa2497799", "value": "", "correctResponse": true }, { "identifier": "854c1411-c0ec-4e6f-a0cc-2d1a66746336", "value": "", "correctResponse": true } ], "potentialConditions": [ { "identifier": "31909ed9-7a36-4018-bb1f-bebc20d426f4", "value": "", "correctResponse": true } ], "parametersToMonitor": [ { "identifier": "34d0f848-0277-44e2-bcf9-95c259394e6b", "value": "", "correctResponse": true }, { "identifier": "50d4bf3b-6cca-4a71-a372-c06f5120cf12", "value": "", "correctResponse": true } ] }'
        ];
    }

    /** @test */

    public function values_within_a_group_should_not_be_repeated()
    {
        $this->qti_question_info['actions_to_take'] = [
            0 => [
                'identifier' => '34d0f848-0277-44e2-bcf9-95c259394e6b',
                'value' => 'some value',
                'correctResponse' => true,
            ],
            1 => [
                'identifier' => '50d4bf3b-6cca-4a71-a372-c06f5120cf12',
                'value' => 'some value',
                'correctResponse' => true,
            ],
        ];

        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $identifier = $this->qti_question_info['parameters_to_monitor'][1]['identifier'];
        $this->assertEquals('some value appears multiple times within the group.', $this->getErrorResponse($response)->specific->{$identifier});

    }

    public function prompt_is_required()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('A prompt is required.', json_decode($response)->errors->qti_prompt[0]);
    }

    /** @test */
    public function each_item_needs_text()
    {
        $identifier = $this->qti_question_info['actions_to_take'][0]['identifier'];
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('Text is required.', $this->getErrorResponse($response)->specific->{$identifier});
    }

    /** @test */
    public function each_item_needs_a_distractor()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('There should be at least one distractor.', $this->getErrorResponse($response)->general);
    }

    public function getErrorResponse($response)
    {
        return json_decode(json_decode($response)->errors->actions_to_take[0]);
    }
}
