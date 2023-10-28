<?php

namespace Tests\Feature\QTI;

use App\SavedQuestionsFolder;
use App\Traits\Test;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use function factory;

class SelectChoiceTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->saved_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id, 'type' => 'my_questions']);
        $this->directory = 'test directory';
        $this->filename = 'some filename';


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
            "license"=>"publicdomain",
            "license_version" => null,
            "qti_select_choice_account1" => 'sdsdf',
            "qti_json" => '{"questionType":"select_choice","responseDeclaration":{"correctResponse":[{"value":"5777"},{"value":"9974"},{"value":"4943"},{"value":"7415"}]},"outcomeDeclaration":{"@attributes":{"identifier":"SCORE","cardinality":"single","baseType":"float"}},"itemBody":"<div><p>On March 1, Dilbert Inc sells 2,000 units to Tundra Inc for $5\/unit or a total of $10,000.  The cost is $3\/unit.  Credit terms are 2\/10, N\/30<\/p><p>What is Tundras journal entry to record their purchase?<\/p><p>DR:  [account1]    [amount1]<\/p><p><\/p><\/div>","inline_choice_interactions":{"account1":[{"value":"5777","text":"Inventory","correctResponse":true},{"value":"7899","text":"Accounts Payable","correctResponse":false},{"value":"5631","text":"Accounts Receivable","correctResponse":false},{"value":"7369","text":"Sales","correctResponse":false}],"amount1":[{"value":"4943","text":"10,000","correctResponse":true},{"value":"3175","text":"6,000","correctResponse":false},{"value":"7659","text":"5","correctResponse":false},{"value":"2329","text":"3","correctResponse":false}]}}',
        ];
        $this->qti_job_id = DB::table('qti_jobs')->insertGetId([
            'user_id' => $this->user->id,
            'qti_source' => 'v2.2',
            'public' => 1,
            'folder_id' => factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id])->id,
            'license' => 'Public domain',
            'qti_directory' => $this->directory]);
    }

    /** @test */
    public function each_identifier_should_have_at_least_two_choices()
    {
        $qti_array = json_decode($this->qti_question_info['qti_json'], true);
        $qti_array['inline_choice_interactions']['account1'] = [[
            "value" => "5777",
            "text" => "Inventory",
            "correctResponse" => true]];

        $this->qti_question_info['qti_json'] = json_encode($qti_array);
        $response['errors']['qti_select_choice_account1'] = ["The identifier [account1] should have at least 2 choices.<br>"];
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson($response);

    }



    /** @test */
    public function no_choices_should_be_blank()
    {
        $qti_array = json_decode($this->qti_question_info['qti_json'], true);
        $qti_array['inline_choice_interactions']['account1'] = [[
            "value" => "5237",
            "text" => "wefwefwef",
            "correctResponse" => true],
        [
            "value" => "5777",
            "text" => "",
            "correctResponse" => true]];

        $this->qti_question_info['qti_json'] = json_encode($qti_array);
        $response['errors']['qti_select_choice_account1'] = ["The identifier [account1] has a blank choice.<br>"];
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson($response);
    }

    /** @test */
    public function choices_cannot_repeat()

    {
        $repeated_text = 'some text';
        $qti_array = json_decode($this->qti_question_info['qti_json'], true);
        $qti_array['inline_choice_interactions']['account1'] = [[
            "value" => "5237",
            "text" => "some text",
            "correctResponse" => true],
            [
                "value" => "5777",
                "text" => "some text",
                "correctResponse" => true]];

        $this->qti_question_info['qti_json'] = json_encode($qti_array);
        $response['errors']['qti_select_choice_account1'] = ["The choice '$repeated_text' appears multiple times under the identifier [account1].<br>"];
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson($response);
    }

}
