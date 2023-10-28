<?php

namespace Tests\Feature\Nursing;

use App\SavedQuestionsFolder;
use App\User;
use Tests\TestCase;

class DropDownRationaleTriadTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->saved_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id, 'type' => 'my_questions']);


        $this->triad_qti_question_info = ["question_type" => "assessment",
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
            "qti_item_body" => "<p>This is some [condition] and this is a [rationale] and this is another [rationale].</p>",
            'qti_select_choice_condition' => [
                [
                    "value" => "6e9ef270-79f9-421c-b90e-191092d6dc88",
                    "text" => "This is correct",
                    "correctResponse" => true
                ],
                [
                    "value" => "df917df0-b8d6-4866-9637-94163d4379c0",
                    "text" => "noe",
                    "correctResponse" => false
                ],
                [
                    "value" => "963fd506-b148-4a06-ac3f-eb383d13e7d0",
                    "text" => "another condition distratractor",
                    "correctResponse" => false
                ]
            ],
            'qti_select_choice_rationales' => [
                [
                    "value" => "e2bb998c-a67c-428d-9631-a40a3b008f54",
                    "text" => "yep",
                    "correctResponse" => true
                ],
                [
                    "value" => "5cd0bff9-1de4-4192-91a6-52e0a3ae8c67",
                    "text" => "another yep",
                    "correctResponse" => true
                ],
                [
                    "value" => "945c9570-9885-4524-a225-054d2293c104",
                    "text" => "boipe",
                    "correctResponse" => false
                ]
            ],
            "qti_json" => '{"questionType":"drop_down_rationale_triad","responseDeclaration":{"correctResponse":[]},"itemBody":"<p>This is some [condition] and this is a [rationale] and this is another [rationale].</p>\n","inline_choice_interactions":{"condition":[{"value":"6e9ef270-79f9-421c-b90e-191092d6dc88","text":"This is correct","correctResponse":true},{"value":"df917df0-b8d6-4866-9637-94163d4379c0","text":"noe","correctResponse":false},{"value":"963fd506-b148-4a06-ac3f-eb383d13e7d0","text":"another condition distratractor","correctResponse":false}],"rationales":[{"value":"e2bb998c-a67c-428d-9631-a40a3b008f54","text":"yep","correctResponse":true},{"value":"5cd0bff9-1de4-4192-91a6-52e0a3ae8c67","text":"another yep","correctResponse":true},{"value":"945c9570-9885-4524-a225-054d2293c104","text":"boipe","correctResponse":false}]},"dropDownRationaleType":"drop_down_rationale","dropDownCloze":true,"feedback":{"correct":"","incorrect":""},"jsonType":"question_json"}'
        ];
    }

    /** @test */
    public function triad_must_have_three_to_five()
    {
        unset($this->triad_qti_question_info['qti_select_choice_condition'][0]);
        $response = json_decode($this->actingAs($this->user)->postJson("/api/questions",
            $this->triad_qti_question_info)
            ->getContent(), 1);
        $this->assertEquals('There should be between 3 and 5 conditions.', json_decode($response['errors']['qti_select_choice_condition'][0], 1)['condition']['general'][0]);

    }


    /** @test */
    public function no_item_should_repeat()
    {
        $this->triad_qti_question_info['qti_select_choice_condition'][0] = $this->triad_qti_question_info['qti_select_choice_condition'][1];
        $response = json_decode($this->actingAs($this->user)->postJson("/api/questions",
            $this->triad_qti_question_info)
            ->getContent(), 1);
        $value = $this->triad_qti_question_info['qti_select_choice_condition'][1]['value'];
        $text =  $this->triad_qti_question_info['qti_select_choice_condition'][1]['text'];
        //dd(json_decode($response['errors']['qti_select_choice_condition'][0], 1)['condition']['specific'][$value][0]);
        $this->assertEquals("$text appears more than once.", json_decode($response['errors']['qti_select_choice_condition'][0], 1)['condition']['specific'][$value][0]);

    }

    /** @test */
    public function text_is_required()
    {
        $this->triad_qti_question_info['qti_select_choice_condition'][0]['text'] = '';
        $response = json_decode($this->actingAs($this->user)->postJson("/api/questions",
            $this->triad_qti_question_info)
            ->getContent(), 1);
        $value = $this->triad_qti_question_info['qti_select_choice_condition'][0]['value'];
        //dd(json_decode($response['errors']['qti_select_choice_condition'][0], 1)['condition']['specific'][$value][0]);
        $this->assertEquals("This field is required.", json_decode($response['errors']['qti_select_choice_condition'][0], 1)['condition']['specific'][$value][0]);

    }


}
