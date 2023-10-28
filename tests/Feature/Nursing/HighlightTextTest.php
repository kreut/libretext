<?php

namespace Tests\Feature\Nursing;

use App\SavedQuestionsFolder;
use App\User;
use Tests\TestCase;

class HighlightTextTest extends TestCase
{
    /**
     * @var array
     */
    private $qti_question_info;

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
            "a11y_auto_graded_question_id" =>null,
            "answer_html" => null,
            "solution_html" => null,
            "notes" => null,
            "hint" => null,
            "license" => "publicdomain",
            "license_version" => null,
            "qti_prompt" => "<p>This is [correct1] and [incorrect1] and [correct2].<\/p>\n",
            'responses' => [
                0 => [
                    'text' => 'correct1',
                    'correctResponse' => true,
                    'identifier' => '95b868ad-9e5e-4be4-a0c7-bb50552fb02f'
                ],
                1 => [
                    'text' => 'incorrect1',
                    'correctResponse' => false,
                    'identifier' => '61979b1d-f73b-49e3-8bc7-7e75b32b1448'
                ],
                2 => [
                    'text' => 'correct2',
                    'correctResponse' => true,
                    'identifier' => 'a0316054-1832-425c-82be-feb7e1ba4251'
                ],
            ],
            "qti_json" => '{"questionType":"highlight_text","prompt":"<p>This is [correct1] and [incorrect1] and [correct2].<\/p>\n","responses":[{"text":"correct1","correctResponse":true,"identifier":"95b868ad-9e5e-4be4-a0c7-bb50552fb02f","selected":true},{"text":"incorrect1","correctResponse":false,"identifier":"61979b1d-f73b-49e3-8bc7-7e75b32b1448","selected":true},{"text":"correct2","correctResponse":true,"identifier":"a0316054-1832-425c-82be-feb7e1ba4251","selected":true}]}'
        ];
    }

    /** @test */
    public function must_choose_response_or_distractor()
    {
        $this->qti_question_info['responses'][0]['correctResponse'] = null;
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();
        $identifier = $this->qti_question_info['responses'][0]['identifier'];
        $this->assertEquals('Please choose Correct Response or Distractor.', json_decode(json_decode($response)->errors->responses[0],1)[$identifier]);
    }

    /** @test */
    public function no_repeated_brackets()
    {
        $this->qti_question_info['qti_prompt'] = '[term] and again [term] and [some other term]';
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();

        $this->assertEquals('[term] is repeated multiple times.  The highlighted text should be unique.', json_decode($response)->errors->qti_prompt[0]);

    }

    /** @test */
    public function prompt_is_required()
    {
        $this->qti_question_info['qti_prompt'] = '';
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();

        $this->assertEquals('A prompt is required.', json_decode($response)->errors->qti_prompt[0]);

    }

    /** @test */
    public function at_least_two_brackets()
    {
        $this->qti_question_info['qti_prompt'] = '[a] and nothing else';
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();

        $this->assertEquals('You need at least two bracketed terms.', json_decode($response)->errors->qti_prompt[0]);

    }

    /** @test */
    public function no_more_than_ten_brackets()
    {
        $this->qti_question_info['qti_prompt'] = '[1] [2] [3] [4] [5] [6] [7] [8] [9] [10] [11]';
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();

        $this->assertEquals('You need at most 10 bracketed terms.', json_decode($response)->errors->qti_prompt[0]);

    }


    /** @test */
    public function brackets_cannot_be_empty()
    {
        $this->qti_question_info['qti_prompt'] = '[term] and again []';
        $response = $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->getContent();

        $this->assertEquals('None of your brackets should be empty.', json_decode($response)->errors->qti_prompt[0]);

    }


}
