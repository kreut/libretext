<?php

namespace Tests\Feature\Instructors;

use App\SavedQuestionsFolder;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Traits\Test;

class QtiTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->saved_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id, 'type' => 'my_questions']);
        $this->directory = 'test directory';
        $this->filename = 'some filename';
        $this->qti_file_info = ['directory' => $this->directory,
            'filename' => $this->filename,
            'author' => "{$this->user->first_name} {$this->user->last_name}",
            'folder_id' => $this->saved_questions_folder->id,
            'license' => 'some license',
            'license_version' => null];
        $this->qti_zip = $this->qti_file_info;
        $this->qti_zip['qti_file'] = 'test_qti.zip';
        $this->qti_zip['import_template'] = 'qti';


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
            "license" => null,
            "license_version" => null,
            "qti_prompt" => "<p>This is my prompt</p>",
            "qti_correct_response" => "adapt-qti-2",
            "qti_simple_choice_0" => "some response",
            "qti_simple_choice_1" => "some other response",
            "qti_json" => '{"@attributes":{"identifier":"","title":"","adaptive":"false","timeDependent":"false"},"responseDeclaration":{"@attributes":{"identifier":"RESPONSE","cardinality":"single","baseType":"identifier"},"correctResponse":{"value":"adapt-qti-2"}},"outcomeDeclaration":{"@attributes":{"identifier":"SCORE","cardinality":"single","baseType":"float"}},"itemBody":{"prompt":"<p>This is my prompt</p>\n","choiceInteraction":{"@attributes":{"responseIdentifier":"RESPONSE","shuffle":"false","maxChoices":"1"},"simpleChoice":[{"@attributes":{"identifier":"adapt-qti-1"},"value":"some response"},{"@attributes":{"identifier":"adapt-qti-2"},"value":"some other response"}]}}}'
        ];
        $this->qti_xml = '<?xml version="1.0" encoding="UTF-8"?>
<!-- Template for choice interaction item -->
<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p2  http://www.imsglobal.org/xsd/qti/qtiv2p2/imsqti_v2p2.xsd" identifier="proola.org/items/1482" title="Growth: Definition" adaptive="false" timeDependent="false">
  <responseDeclaration identifier="RESPONSE" cardinality="single" baseType="identifier">
    <correctResponse>
        <value>ChoiceC</value></correctResponse>
  </responseDeclaration>
  <outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float">
    <defaultValue>
      <value>0</value>
    </defaultValue>
  </outcomeDeclaration>
  <itemBody>
    <prompt>
      <p>Economic growth can best be defined as</p>
    </prompt>
    <choiceInteraction responseIdentifier="RESPONSE" shuffle="false" maxChoices="1">
        <simpleChoice identifier="ChoiceA">Increasing output per citizen</simpleChoice><simpleChoice identifier="ChoiceB">Increasing output given our current resources</simpleChoice><simpleChoice identifier="ChoiceC">Increasing potential output</simpleChoice></choiceInteraction>
  </itemBody>
  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p2/rptemplates/match_correct"/>
</assessmentItem>';
        $this->qti_import_id = DB::table('qti_imports')->insertGetId(['user_id' => $this->user->id,
            'directory' => $this->directory,
            'filename' => $this->filename,
            'xml' => $this->qti_xml]);


    }

    public function should_be_of_the_correct_version()
    {

        $qti_html = str_replace('imsqti_v2p2', 'imsqti_v2p3', $this->qti_xml);
        DB::table('qti_imports', $this->qti_import_id)
            ->update(['xml' => $qti_html]);
        $this->actingAs($this->user)->postJson("/api/qti-import", $this->qti_file_info)
            ->assertJson(['message' => "Currently only QTI version 2.2 is accepted."]);
    }

    /** @test */

    public function must_be_simple_choice_problem()
    {

        $qti_html = str_replace('cardinality="single"', 'cardinality="double"', $this->qti_xml);
        DB::table('qti_imports', $this->qti_import_id)
            ->update(['xml' => $qti_html]);
        $this->actingAs($this->user)->postJson("/api/qti-import", $this->qti_file_info)
            ->assertJson(['message' => "$this->filename is not a simple choice QTI problem."]);

    }


    /** @test */
    public function there_should_be_at_least_two_choices()
    {
        unset($this->qti_question_info['qti_simple_choice_1']);
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_simple_choice_0' => [
                "You should have at least 2 responses."
            ]
            ]]);
    }

    /** @test */
    public function two_choices_should_not_be_the_same()
    {
        $this->qti_question_info = str_replace('some other response', 'some response', $this->qti_question_info);

        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_simple_choice_0' => [
                "The response 'some response' appears more than once."
            ]
            ]]);
    }

    /** @test * */
    public function simpleChoice_question_can_be_edited_without_repeat_issue()
    {

        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['type' => 'success']);
        $question_id = DB::table('questions')->where('qti_json', $this->qti_question_info['qti_json'])->first()->id;
        DB::table('questions')->count();
        $this->qti_question_info['hint'] = 'sdfdsfsdf';
        $this->actingAs($this->user)->patchJson("/api/questions/$question_id",
            $this->qti_question_info)
            ->assertJson(['type' => 'success']);
    }


    /** @test * */
    public function simpleChoice_question_cannot_be_repeated()
    {

        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['type' => 'success']);
        $question = DB::table('questions')->orderBy('id', 'desc')->first();
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_prompt' => [
                "This question is identical to the native question with ADAPT ID $question->id."
            ]
            ]]);
    }

    /** @test * */
    public function qti_import_cannot_be_repeated()
    {

        $this->actingAs($this->user)->postJson("/api/qti-import", $this->qti_file_info)
            ->assertJson(['type' => "success"]);
        $question = DB::table('questions')->orderBy('id', 'desc')->first();
        $this->actingAs($this->user)->postJson("/api/qti-import", $this->qti_file_info)
            ->assertJson(['message' => "This question is identical to the native question with ADAPT ID $question->id."]);

    }


    /** @test * */
    public function prompt_is_required()
    {
        unset($this->qti_question_info['qti_prompt']);
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJsonValidationErrors('qti_prompt');
    }

    /** @test * */
    public function prompt_should_have_length()
    {
        $this->qti_question_info['qti_prompt'] = '';
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => [
                'qti_prompt' => [
                    "A prompt is required."
                ]
            ]
            ]);

    }

    /** @test * */
    public function correct_response_is_required()
    {
        unset($this->qti_question_info['qti_correct_response']);
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => [
                'qti_simple_choice_0' => [
                    "You didn't select any of the responses as being correct."
                ]
            ]
            ]);
    }


    /** @test * */
    public function xml_must_be_valid()
    {
        $filename = 'some other name';
        DB::table('qti_imports')->insert([
            'directory' => $this->directory,
            'filename' => $filename,
            'xml' => 'invalid xml',
            'user_id' => $this->user->id
        ]);

        $this->qti_file_info['filename'] = $filename;
        $this->actingAs($this->user)->postJson("/api/qti-import",
            $this->qti_file_info)
            ->assertJson(['message' => "XML is not well-formed:  Start tag expected, '<' not found"]);
    }

    /** @test * */
    public function file_must_exist_in_the_database()
    {
        $this->qti_file_info['filename'] = 'some other name';
        $this->actingAs($this->user)->postJson("/api/qti-import",
            $this->qti_file_info)
            ->assertJson(['message' => $this->qti_file_info['filename'] . " does not exist in the database."]);
    }


    /** @test * */
    public function only_instructors_can_import_qti()
    {
        $this->user->role = 3;
        $this->user->save();
        $this->actingAs($this->user)->postJson("/api/qti-import",
            $this->qti_file_info)
            ->assertJson(['message' => "You are not allowed to import QTI questions."]);
    }


    /** @test * */
    public function license_is_required()
    {
        unset($this->qti_file_info['license']);
        $this->actingAs($this->user)->postJson("/api/qti-import",
            $this->qti_file_info)
            ->assertJson(['message' => "A license is required."]);
    }

    /** @test * */
    public function author_is_required()
    {
        unset($this->qti_file_info['author']);
        $this->actingAs($this->user)->postJson("/api/qti-import",
            $this->qti_file_info)
            ->assertJson(['message' => "An author is required."]);
    }

    /** @test * */
    public function folder_is_required()
    {
        unset($this->qti_file_info['folder_id']);
        $this->actingAs($this->user)->postJson("/api/qti-import",
            $this->qti_file_info)
            ->assertJson(['message' => "A folder is required."]);
    }

    /** @test * */
    public function must_own_folder()
    {
        $this->qti_file_info['folder_id'] = 12831238;
        $this->actingAs($this->user)->postJson("/api/qti-import",
            $this->qti_file_info)
            ->assertJson(['message' => "That is not your folder."]);
    }


}
