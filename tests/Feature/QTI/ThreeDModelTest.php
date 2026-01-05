<?php

namespace Tests\Feature\QTI;

use App\SavedQuestionsFolder;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ThreeDModelTest extends TestCase
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
            'open_ended_submission_type' => '0',
            'qti_prompt' => '',
            'parameters' => ["modelID" => "ssss",
                "BGImage" => 'sdfsdfsdf',
                "annotations" => 'sdfsdfsdf',
                "mode" => "some mode",
                "BGColor" => "))))",
                "piece" => "",
                "modelOffset" => "",
                "cameraOffset" => "",
                "selectionColor" => "",
                "panel" => "",
                "autospin" => "",
                "STLmatCol" => "wfewefwef",
                "hideDistance" => ""],
            "qti_json" => '{"questionType":"three_d_model_multiple_choice","parameters":{"modelID":"","mode":"","BGColor":"","piece":"","modelOffset":"","cameraOffset":"","selectionColor":"","panel":"","autospin":"","STLmatCol":"","hideDistance":""}}'
        ];
    }

    /** @test * */
    public function mark_component_must_store_valid_input()
    {
        $response = $this->actingAs($this->user)->postJson("/api/questions", $this->qti_question_info)
            ->getContent();

        $errors = json_decode($response)->errors;
        $this->assertEquals('A prompt is required.', $errors->qti_prompt[0]);
        $parameter_errors = json_decode($errors->parameters[0]);

        $this->assertEquals('The modelID is not a valid URL.', $parameter_errors->modelID);
        $this->assertEquals('The BGImage is not a valid URL.', $parameter_errors->BGImage);
        $this->assertEquals('The annotations is not a valid URL.', $parameter_errors->annotations);
        $this->assertEquals('some mode is not a valid mode.', $parameter_errors->mode);
        $this->assertEquals('BGColor is not a valid hexadecimal.', $parameter_errors->BGColor);
        $this->assertEquals('STLmatCol is not a valid hexadecimal.', $parameter_errors->STLmatCol);
    }


}
