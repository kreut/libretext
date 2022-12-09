<?php

namespace Tests\Feature\Nursing;

use App\SavedQuestionsFolder;
use App\User;
use Tests\TestCase;

class DropDownRationaleTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->saved_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id, 'type' => 'my_questions']);
        $this->dyad_qti_question_info = ["question_type" => "assessment",
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
            "qti_item_body" => "<p>[id1] and [id2]</p>\n",
            'qti_select_choice_id1' => [
                0 => [
                    'value' => '1670619527102',
                    'text' => 'correct',
                    'correctResponse' => true,
                ],
                1 => [
                    'value' => 'da46756e-bfaf-4b1b-92fa-091d594ac43b',
                    'text' => 'nope',
                    'correctResponse' => false,
                ],
            ],
            'qti_select_choice_id2' => [
                0 => [
                    'value' => '1670619527102',
                    'text' => 'correct',
                    'correctResponse' => true,
                ],
                1 => [
                    'value' => 'da46756e-bfaf-4b1b-92fa-091d594ac43b',
                    'text' => 'nope',
                    'correctResponse' => false,
                ],
            ],
            "qti_json" => '{"questionType":"drop_down_rationale","responseDeclaration":{"correctResponse":[]},"itemBody":"<p>[id1] and [id2]</p>\n","inline_choice_interactions":{"id1":[{"value":"1670619527102","text":"correct","correctResponse":true},{"value":"da46756e-bfaf-4b1b-92fa-091d594ac43b","text":"nope","correctResponse":false}],"id2":[{"value":"1670619530669","text":"correct 2","correctResponse":true},{"value":"9b6b6920-1f7f-4664-983c-cd6009f4542f","text":"nope 2","correctResponse":false}]},"dropDownRationaleType":"dyad","feedback":{"correct":"<p>All right</p>\n","incorrect":"<p>Not all right</p>\n"},"showResponseFeedback":false,"jsonType":"question_json"}'
        ];

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
            "a11y_technology" => null,
            "a11y_technology_id" => null,
            "answer_html" => null,
            "solution_html" => null,
            "notes" => null,
            "hint" => null,
            "license" => "publicdomain",
            "license_version" => null,
            "qti_item_body" => "<p>[first] and [second] and [third]</p>",
            'qti_select_choice_first' => [
                0 => [
                    'value' => '1670620732587',
                    'text' => 'first correct',
                    'correctResponse' => true,
                ],
                1 => [
                    'value' => 'ba3a4679-f226-40ae-8ff0-cb8fc283904b',
                    'text' => 'nope 1',
                    'correctResponse' => false,
                ],
            ],
            'qti_select_choice_second' => [
                0 => [
                    'value' => '1670620734948',
                    'text' => 'second correct',
                    'correctResponse' => true,
                ],
                1 => [
                    'value' => '823c40c3-e530-4993-9909-75b8a691f34e',
                    'text' => 'nope 2',
                    'correctResponse' => false,
                ],
            ],
            'qti_select_choice_third' => [
                0 => [
                    'value' => '1670620737580',
                    'text' => 'third correct',
                    'correctResponse' => true,
                ],
                1 => [
                    'value' => '204d5aeb-267b-405d-a46b-7faa55ec8126',
                    'text' => 'nope 3',
                    'correctResponse' => false,
                ],
            ],
            "qti_json" => '{"questionType":"drop_down_rationale","responseDeclaration":{"correctResponse":[]},"itemBody":"<p>[first] and [second] and [third]</p>\n","inline_choice_interactions":{"first":[{"value":"1670620732587","text":"first correct","correctResponse":true},{"value":"ba3a4679-f226-40ae-8ff0-cb8fc283904b","text":"nope 1","correctResponse":false}],"second":[{"value":"1670620734948","text":"second correct","correctResponse":true},{"value":"823c40c3-e530-4993-9909-75b8a691f34e","text":"nope 2","correctResponse":false}],"third":[{"value":"1670620737580","text":"third correct","correctResponse":true},{"value":"204d5aeb-267b-405d-a46b-7faa55ec8126","text":"nope 3","correctResponse":false}]},"dropDownRationaleType":"triad","feedback":{"correct":"<p>all correct</p>\n","incorrect":"<p>not all correct</p>\n"},"showResponseFeedback":false,"jsonType":"question_json"}'
        ];
    }

    /** @test */
    public function dyad_must_have_two_drop_downs()
    {
        $qti_json = json_decode($this->dyad_qti_question_info['qti_json'], 1);
        unset($qti_json['inline_choice_interactions']['id1']);
        unset($this->dyad_qti_question_info['qti_select_choice_id1']);
        $this->dyad_qti_question_info['qti_json'] = json_encode($qti_json);
        $response = json_decode($this->actingAs($this->user)->postJson("/api/questions",
            $this->dyad_qti_question_info)
            ->getContent(), 1);
        $this->assertEquals('Drop-down rationale dyads should have exactly 2 drop downs.', $response['errors']['qti_item_body'][0]);

    }

    /** @test */
    public function triad_must_have_three_drop_downs()
    {
        $qti_json = json_decode($this->triad_qti_question_info['qti_json'], 1);
        unset($qti_json['inline_choice_interactions']['third']);
        unset($this->triad_qti_question_info['qti_select_choice_third']);
        $this->triad_qti_question_info['qti_json'] = json_encode($qti_json);
        $response = json_decode($this->actingAs($this->user)->postJson("/api/questions",
            $this->triad_qti_question_info)
            ->getContent(), 1);
        $this->assertEquals('Drop-down rationale triads should have exactly 3 drop downs.', $response['errors']['qti_item_body'][0]);


    }


}
