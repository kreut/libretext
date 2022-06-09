<?php

namespace Tests\Feature\QTI;

use App\SavedQuestionsFolder;
use App\Traits\Test;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use function factory;

class MatchingTest extends TestCase
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
            "a11y_technology" => null,
            "a11y_technology_id" => null,
            "answer_html" => null,
            "solution_html" => null,
            "notes" => null,
            "hint" => null,
            "license" => null,
            "license_version" => null,
            "qti_prompt" => 'some prompt',
            "qti_matching_distractor_0" => "<p>2</p>\n",
            "qti_matching_matching_term_0" => "<p>1</p>\n",
            "qti_matching_term_to_match_0" => "<p>1</p>\n",
            "qti_json" => '{"questionType":"matching","prompt":"some prompt","termsToMatch":[{"identifier":"1654952557281","termToMatch":"<p>1</p>\n","matchingTermIdentifier":"1654952557281-1","feedback":""}],"possibleMatches":[{"identifier":"1654952557281-1","matchingTerm":"<p>1</p>\n"},{"identifier":"1654952563168","matchingTerm":"<p>2</p>\n"}]}'
        ];
        $this->qti_job_id = DB::table('qti_jobs')->insertGetId([
            'user_id' => $this->user->id,
            'qti_source' => 'v2.2',
            'public' => 1,
            'folder_id' => factory(SavedQuestionsFolder::class)->create(['user_id' => $this->user->id])->id,
            'license' => 'Public domain',
            'qti_directory' => $this->directory]);
    }


    /** @test * */
    public
    function possible_matches_must_be_unique()
    {
        $qti_json = json_decode($this->qti_question_info['qti_json'], true);
        $qti_json['possibleMatches'][1]['matchingTerm'] = $qti_json['possibleMatches'][0]['matchingTerm'];
        $this->qti_question_info['qti_json'] = json_encode($qti_json);
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_matching_term_to_match_0' => [$qti_json['possibleMatches'][1]['matchingTerm'] . ' appears multiple times as a matching term.']]]);
    }


    /** @test * */
    public
    function at_least_two_matchings_are_required()
    {
        $qti_json = json_decode($this->qti_question_info['qti_json'], true);
        $qti_json['possibleMatches'] = [$qti_json['possibleMatches'][0]];

        $this->qti_question_info['qti_json'] = json_encode($qti_json);
        unset($this->qti_question_info['qti_matching_distractor_0']);
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_matching_term_to_match_0' => ['There should be at least 2 matching choices.']]]);

    }


    /** @test */
    public
    function every_term_to_match_requires_text()
    {
        $this->qti_question_info['qti_matching_term_to_match_0'] = '';
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_matching_term_to_match_0' => ['The term to match from Matching 1 is required.']]]);
    }

    /** @test */
    public
    function every_matching_term_requires_text()
    {

        $this->qti_question_info['qti_matching_matching_term_0'] = '';
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_matching_matching_term_0' => ['The matching term from Matching 1 is required.']]]);

    }

    /** @test */
    public
    function every_distractor_requires_text()
    {

        $this->qti_question_info['qti_matching_distractor_0'] = '';
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJson(['errors' => ['qti_matching_distractor_0' => ['Distractor 1 is required.']]]);

    }

    /** @test * */
    public function prompt_is_required()
    {
        unset($this->qti_question_info['qti_prompt']);
        $this->actingAs($this->user)->postJson("/api/questions",
            $this->qti_question_info)
            ->assertJsonValidationErrors('qti_prompt');

    }


}
