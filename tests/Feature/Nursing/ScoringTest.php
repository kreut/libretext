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

    /** @test */
    public function drag_and_drop_cloze_is_scored_correctly()
    {

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