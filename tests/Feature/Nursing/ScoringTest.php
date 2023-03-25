<?php

namespace Tests\Feature\Nursing;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Question;
use App\SavedQuestionsFolder;
use App\Section;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Traits\Test;

class ScoringTest extends TestCase
{
    use Test;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        $this->student_user = factory(User::class)->create();
        $this->points = 10;
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id, $this->student_user->id);
        $this->saved_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id, 'type' => 'my_questions']);
    }

    /** @test */
    public function drop_down_rationale_triad_is_scored_correctly()
    {
        $question = $this->makeNursingQuestion('{"questionType":"drop_down_rationale_triad","responseDeclaration":{"correctResponse":[]},"itemBody":"<p>This is some [condition] and this is a [rationale] and this is another [rationale].</p>\n","inline_choice_interactions":{"condition":[{"value":"6e9ef270-79f9-421c-b90e-191092d6dc88","text":"This is correct","correctResponse":true},{"value":"df917df0-b8d6-4866-9637-94163d4379c0","text":"noe","correctResponse":false},{"value":"963fd506-b148-4a06-ac3f-eb383d13e7d0","text":"another condition distratractor","correctResponse":false}],"rationales":[{"value":"e2bb998c-a67c-428d-9631-a40a3b008f54","text":"yep","correctResponse":true},{"value":"5cd0bff9-1de4-4192-91a6-52e0a3ae8c67","text":"another yep","correctResponse":true},{"value":"945c9570-9885-4524-a225-054d2293c104","text":"boipe","correctResponse":false}]},"dropDownRationaleType":"drop_down_rationale","dropDownCloze":true,"feedback":{"correct":"","incorrect":""},"jsonType":"question_json"}');
        $this->addQuestionToAssignment($question);

        //can't submit the same rationale more than once
        $submission = '[{"identifier":"condition","value":"df917df0-b8d6-4866-9637-94163d4379c0"},{"identifier":"rationale-1","value":"e2bb998c-a67c-428d-9631-a40a3b008f54"},{"identifier":"rationale-2","value":"e2bb998c-a67c-428d-9631-a40a3b008f54"}]';
        $submission = $this->createSubmission($question, $submission);
        $this->assertContains('You have chosen the same rationale twice.', $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)->getContent());


        //missed the first so get 0
        $submission = '[{"identifier":"condition","value":"df917df0-b8d6-4866-9637-94163d4379c0"},{"identifier":"rationale-1","value":"e2bb998c-a67c-428d-9631-a40a3b008f54"},{"identifier":"rationale-2","value":"5cd0bff9-1de4-4192-91a6-52e0a3ae8c67"}]';
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals(0, $actual_score);
//get all 3 correct
        $submission = '[{"identifier":"condition","value":"6e9ef270-79f9-421c-b90e-191092d6dc88"},{"identifier":"rationale-1","value":"e2bb998c-a67c-428d-9631-a40a3b008f54"},{"identifier":"rationale-2","value":"5cd0bff9-1de4-4192-91a6-52e0a3ae8c67"}]';
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points, $actual_score);

        //get first correct but miss one of the other two
        $submission = '[{"identifier":"condition","value":"6e9ef270-79f9-421c-b90e-191092d6dc88"},{"identifier":"rationale-1","value":"945c9570-9885-4524-a225-054d2293c104"},{"identifier":"rationale-2","value":"5cd0bff9-1de4-4192-91a6-52e0a3ae8c67"}]';
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points / 2, $actual_score);


    }

    /** @test */
    public function drag_and_drop_cloze_is_scored_correctly()
    {
        $question = $this->makeNursingQuestion('{"questionType":"drag_and_drop_cloze","prompt":"<p>The client is at risk for developing [select] and [select].&nbsp;<\/p>\n","correctResponses":[{"identifier":"89df7995-b2f5-405c-8d4c-00bf6d1df2e6","value":"high blood pressure"},{"identifier":"16b7b948-a89b-43e1-bcad-af51341b72d7","value":"a heart attack"}],"distractors":[{"identifier":"c02f5a1c-9516-487d-a790-5d6f7e5882ee","value":"d1"},{"identifier":"d6657d88-f037-4065-a046-f494044662f5","value":"d2"}],"feedback":{"correct":"<p>all correct<\/p>\n","incorrect":"<p>not all correct<\/p>\n"},"selectOptions":[{"value":null,"text":"Please choose an option"},{"value":"89df7995-b2f5-405c-8d4c-00bf6d1df2e6","text":"high blood pressure"},{"value":"16b7b948-a89b-43e1-bcad-af51341b72d7","text":"a heart attack"},{"value":"c02f5a1c-9516-487d-a790-5d6f7e5882ee","text":"d1"},{"value":"d6657d88-f037-4065-a046-f494044662f5","text":"d2"}],"jsonType":"question_json"}');
        $this->addQuestionToAssignment($question);

        //half-right
        $submission = '["c02f5a1c-9516-487d-a790-5d6f7e5882ee","16b7b948-a89b-43e1-bcad-af51341b72d7"]';
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points / 2, $actual_score);
//all correct
        $submission = '["16b7b948-a89b-43e1-bcad-af51341b72d7","89df7995-b2f5-405c-8d4c-00bf6d1df2e6"]';
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points, $actual_score);


    }


    /** @test */
    public function dyad_is_scored_correctly()
    {//full credit
        $question = $this->makeNursingQuestion('{ "questionType": "drop_down_rationale", "responseDeclaration": { "correctResponse": [] }, "itemBody": "<p>[id1] and [id2]</p>\n", "inline_choice_interactions": { "id1": [ { "value": "1670619527102", "text": "correct", "correctResponse": true }, { "value": "da46756e-bfaf-4b1b-92fa-091d594ac43b", "text": "nope", "correctResponse": false } ], "id2": [ { "value": "1670619530669", "text": "correct 2", "correctResponse": true }, { "value": "9b6b6920-1f7f-4664-983c-cd6009f4542f", "text": "nope 2", "correctResponse": false } ] }, "dropDownRationaleType": "dyad", "feedback": { "correct": "<p>All right</p>\n", "incorrect": "<p>Not all right</p>\n" }, "showResponseFeedback": false, "jsonType": "question_json" }');
        $this->addQuestionToAssignment($question);
        $submission = '[{"identifier":"id1","value":"1670619527102"},{"identifier":"id2","value":"1670619530669"}]';
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points, $actual_score);

//nothing if only partially correct
        $submission = '[{"identifier":"id1","value":"1670619527102"},{"identifier":"id2","value":"9b6b6920-1f7f-4664-983c-cd6009f4542f"}]';
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals(0, $actual_score);


    }


    /** @test */
    public function highlight_table_is_scored_correctly()
    {
        $question = $this->makeNursingQuestion('{"questionType":"highlight_table","colHeaders":["header 1","header 2"],"rows":[{"header":"Row 1","prompt":"[correct1] andand and lksdflksdflkjsdf and [correct2] and [incorrect]","responses":[{"text":"correct1","correctResponse":true,"identifier":"eeaa2a84-79b9-4eea-a63b-fd033f71df67","selected":true},{"text":"correct2","correctResponse":true,"identifier":"da183fa6-7ebb-44e2-ba1a-514c5db733e6","selected":true},{"text":"incorrect","correctResponse":false,"identifier":"cd473ff9-0b22-4be5-8bc8-a81cd7fbaf37","selected":true}]},{"header":"Row 2","prompt":"[correct3] and [incorrect2]","responses":[{"text":"correct3","correctResponse":true,"identifier":"619992eb-e8d6-4217-83ef-2e9ab8372c79","selected":true},{"text":"incorrect2","correctResponse":false,"identifier":"48798b24-0bc5-44f8-a7d3-962d81537750","selected":false}]}]}');
        $this->addQuestionToAssignment($question);
        $submission = '["eeaa2a84-79b9-4eea-a63b-fd033f71df67","da183fa6-7ebb-44e2-ba1a-514c5db733e6","619992eb-e8d6-4217-83ef-2e9ab8372c79"]';
        //3 out of 3 correct
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points, $actual_score);

        $submission = '["eeaa2a84-79b9-4eea-a63b-fd033f71df67","da183fa6-7ebb-44e2-ba1a-514c5db733e6"]';
        //2 out of 3 correct but missing 1.  So, 1 + 1 -1 = 1 or 1/3 points
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals(Round($this->points / 3, 4), $actual_score);

        $submission = '["eeaa2a84-79b9-4eea-a63b-fd033f71df67","da183fa6-7ebb-44e2-ba1a-514c5db733e6","cd473ff9-0b22-4be5-8bc8-a81cd7fbaf37","48798b24-0bc5-44f8-a7d3-962d81537750"]';
        //2 out of correct and 2 incorrect.  So, 0 points.
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals(0, $actual_score);

    }

    /** @test */
    public function highlight_text_is_scored_correctly()
    {
        $question = $this->makeNursingQuestion('{"questionType":"highlight_text","prompt":"<p>This is [correct1] and [incorrect1] and [correct2].<\/p>\n","responses":[{"text":"correct1","correctResponse":true,"identifier":"95b868ad-9e5e-4be4-a0c7-bb50552fb02f","selected":true},{"text":"incorrect1","correctResponse":false,"identifier":"61979b1d-f73b-49e3-8bc7-7e75b32b1448","selected":true},{"text":"correct2","correctResponse":true,"identifier":"a0316054-1832-425c-82be-feb7e1ba4251","selected":true}]}');
        $this->addQuestionToAssignment($question);
        $submission = '["95b868ad-9e5e-4be4-a0c7-bb50552fb02f","61979b1d-f73b-49e3-8bc7-7e75b32b1448","a0316054-1832-425c-82be-feb7e1ba4251"]';
        //2 right and 1 wrong so half correct.
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points * 5 / 10, $actual_score);

//2 right and none wrong
        $submission = '["95b868ad-9e5e-4be4-a0c7-bb50552fb02f","a0316054-1832-425c-82be-feb7e1ba4251"]';
        //2 right and 1 wrong so half correct.
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points * 10 / 10, $actual_score);

    }

    /** @test */
    public function bow_tie_is_scored_correctly()
    {
        $question = $this->makeNursingQuestion('{ "questionType": "bow_tie", "actionsToTake": [ { "identifier": "46067f42-001d-4804-b600-30a8f716ba09", "value": "Correct Action to take 1", "correctResponse": true }, { "identifier": "c3fcabf1-bcd1-4363-8962-8d8a86fcbfae", "value": "Correct Action to take 2", "correctResponse": true }, { "identifier": "8a5e9552-50ae-4bf3-a262-f7928f462ec6", "value": "Distractor 1", "correctResponse": false }, { "identifier": "9cdc4fb9-4a7f-4396-b008-31441263e9a1", "value": "Distractor 2", "correctResponse": false } ], "potentialConditions": [ { "identifier": "e68fd1c8-6bf9-414a-838c-cac8bedbefbe", "value": "Condition 1", "correctResponse": true }, { "identifier": "f4808a9a-5bfa-4fb5-9136-2f557db4ca4e", "value": "Distractor 1", "correctResponse": false }, { "identifier": "7b070869-dd73-447b-a6e0-92257e7f9e95", "value": "Distractor 2", "correctResponse": false } ], "parametersToMonitor": [ { "identifier": "686da0e0-ee06-456b-80ef-c990cbc31802", "value": "Correct Parameter 1", "correctResponse": true }, { "identifier": "4289f376-37dc-422e-bcd0-65fb577e2132", "value": "Correct Parameter 2", "correctResponse": true }, { "identifier": "f9d4327c-6f2d-4192-a815-a67c939a4b66", "value": "Distractor 1", "correctResponse": false } ], "prompt": "<p>This is the bow tie prompt.</p>\n" }');
        $this->addQuestionToAssignment($question);
        //actions to take: 1 correct and 1 incorrect
        //potential condition: 1 correct
        //parameters to monitor: 2 correct
        $submission = '{"actionsToTake":["46067f42-001d-4804-b600-30a8f716ba09","8a5e9552-50ae-4bf3-a262-f7928f462ec6"],"potentialConditions":["e68fd1c8-6bf9-414a-838c-cac8bedbefbe"],"parametersToMonitor":["4289f376-37dc-422e-bcd0-65fb577e2132","686da0e0-ee06-456b-80ef-c990cbc31802"]}';
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);

        //4 out of a possible 5 points
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points * 4 / 5, $actual_score);

    }

    /** @test */
    public function drop_down_table_is_scored_correctly()
    {
        $question = $this->makeNursingQuestion(' {"questionType":"drop_down_table","prompt":"<p>some prompt<\/p>\n","colHeaders":["am","b"],"rows":[{"header":"r1","selected":"8cf986d0-609b-47b3-90c6-3d87980fac4c","responses":[{"identifier":"8cf986d0-609b-47b3-90c6-3d87980fac4c","value":"d1","correctResponse":false},{"identifier":"d3b426e8-dce8-42ed-bbf8-688f76513f6d","value":"correct response 1","correctResponse":true}]},{"header":"r2","selected":"f047a296-d889-4218-9f4b-5bdbafb8cf42","responses":[{"identifier":"f047a296-d889-4218-9f4b-5bdbafb8cf42","value":"d2","correctResponse":false},{"identifier":"00f50b5f-725c-4862-b739-bb36a7926f05","value":"correct response 2","correctResponse":true}]}]}');
        $this->addQuestionToAssignment($question);
        $submission = '["d3b426e8-dce8-42ed-bbf8-688f76513f6d","f047a296-d889-4218-9f4b-5bdbafb8cf42"]';
        //1 right and 1 wrong
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points * 5 / 10, $actual_score);
    }

    /** @test */
    public function multiple_response_grouping_is_scored_correctly()
    {
        $question = $this->makeNursingQuestion('{"questionType":"multiple_response_grouping","prompt":"<p>some promt</p>\n","headers":["h133","h2"],"rows":[{"grouping":"g1","responses":[{"identifier":"31d5ece5-db4e-40eb-97a9-10d7649b5847","value":"correct answer 1","correctResponse":true},{"identifier":"11c73d73-7f25-4253-b665-6f3aee9e0e27","value":"r222","correctResponse":false}]},{"grouping":"g2","responses":[{"identifier":"e49a6830-7f7a-4b3d-87be-0840863decbe","value":"correct answer 2","correctResponse":true},{"identifier":"d32bc2bd-61d6-4e3a-8bbb-c92f48b387ed","value":"r22","correctResponse":false}]}]}');
        $this->addQuestionToAssignment($question);
        $submission = '["31d5ece5-db4e-40eb-97a9-10d7649b5847", "d32bc2bd-61d6-4e3a-8bbb-c92f48b387ed"]';
        //1 right and 1 wrong
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals(0, $actual_score);

    }


    /** @test */
    public function multiple_response_select_n_is_scored_correctly()
    {
        $question = $this->makeNursingQuestion('{"questionType":"multiple_response_select_n","prompt":"<p>select [2] responses</p>\n","numberToSelect":"2","responses":[{"identifier":"8a30470a-3d22-4170-b241-c555ce2bbfd9","value":"good responsef","correctResponse":true},{"identifier":"632bd8ca-38b6-40ff-8dcd-fa21fbca7ed5","value":"distractor 12","correctResponse":false},{"identifier":"4f06ef42-8da5-48d4-bbd7-74c14210c88b","value":"another good responseg","correctResponse":true}]}');
        $this->addQuestionToAssignment($question);
        $submission = '["632bd8ca-38b6-40ff-8dcd-fa21fbca7ed5","4f06ef42-8da5-48d4-bbd7-74c14210c88b"]';
        //1 right and 1 wrong
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points * 5 / 10, $actual_score);
    }

    /** @test */
    public function multiple_response_select_all_that_apply_is_scored_correctly()
    {
        $question = $this->makeNursingQuestion('{"questionType":"multiple_response_select_all_that_apply","prompt":"<p>prompt</p>\n","responses":[{"identifier":"7807064d-10b0-4595-b1cf-c851aa71942e","value":"resopnse 1a","correctResponse":true},{"identifier":"f1de41cd-27d7-4501-aff1-e0a6f6fe931d","value":"distractor 1b","correctResponse":false},{"identifier":"801f6954-e5b3-4fab-88c9-c2c27db45f3d","value":"another correct response","correctResponse":true}]}');
        $this->addQuestionToAssignment($question);
        $submission = '["801f6954-e5b3-4fab-88c9-c2c27db45f3d","f1de41cd-27d7-4501-aff1-e0a6f6fe931d","7807064d-10b0-4595-b1cf-c851aa71942e"]';
        //2 right and 1 wrong so 1+1-1= 1 out of 2 possible correct.
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points * 5 / 10, $actual_score);
    }

    /** @test */
    public function matrix_multiple_choice_is_scored_correctly()
    {
        $question = $this->makeNursingQuestion('{"questionType":"matrix_multiple_choice","prompt":"<p>Some type of prompt</p>\n","headers":["c1wef","c2w","c3efw"],"rows":[{"label":"r1wef","correctResponse":0},{"label":"r2","correctResponse":1}]}');
        $this->addQuestionToAssignment($question);
        $submission = '[0,0]';
        //1 right and 1 wrong so half correct.
        $submission = $this->createSubmission($question, $submission);
        $this->actingAs($this->student_user)->postJson("/api/submissions", $submission)
            ->assertJson(['type' => 'success']);
        $actual_score = DB::table('submissions')->where('question_id', $question->id)->first()->score;
        $this->assertEquals($this->points * 5 / 10, $actual_score);

    }

    function makeNursingQuestion($json)
    {

        return factory(Question::class)->create(['qti_json' => $json]);
    }

    /**
     * @param $question
     * @param $submission
     * @return array
     */
    function createSubmission($question, $submission): array
    {
        return ['assignment_id' => $this->assignment->id, 'question_id' => $question->id, 'technology' => 'qti', 'submission' => $submission];
    }

    function addQuestionToAssignment($question)
    {
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'points' => $this->points,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);
    }
}
