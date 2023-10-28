<?php

namespace Tests\Feature\Nursing;

use App\SavedQuestionsFolder;
use App\User;
use Tests\TestCase;

class DragAndDropClozeTest extends TestCase
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
            "qti_prompt" => '[some_drop_down] is an example.',
            "correct_response" => [],
            "distractors" => [
                0 => [
                    'identifier' => 'e84c7e6e-e06c-4da1-b9c2-bf589a3b5edb',
                    'value' => '',
                ],
            ],
            "qti_json" => '{ "questionType": "drag_and_drop_cloze", "prompt": "", "correctResponses": [], "distractors": [ { "identifier": "0bd83f50-c8ca-4f89-b4cb-baf103f66f8b", "value": "" } ] }'
        ];
    }

    /** @test */
    public function need_at_least_one_drop_down()
    {
        $this->qti_question_info['prompt'] = '';
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $this->assertEquals('You need at least one Correct Response in your prompt.', json_decode($response)->errors->qti_prompt[0]);
    }

    /** @test */
    public function distractors_need_text()
    {
        $identifier = $this->qti_question_info['distractors'][0]['identifier'];
        $this->qti_question_info['prompt'] = '';
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $distractor_error = json_decode(json_decode($response)->errors->distractors[0]);
        $this->assertEquals('Text is required.', $distractor_error->specific->{$identifier});
    }

    /** @test */
    public function there_should_be_at_least_one_distractor()
    {
        $this->qti_question_info['distractors'] = [];
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJsonValidationErrors('distractors');

    }

}

