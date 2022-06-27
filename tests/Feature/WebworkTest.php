<?php

namespace Tests\Feature;

use App\Question;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WebworkTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->question = factory(Question::class)->create(['page_id' =>23482671]);
    }

    /** @test */
    public function non_instructor_cannot_get_webwork_code_from_filepath()
    {

        $this->user->role = 3;
        $this->user->save();
        $this->actingAs($this->user)->postJson("/api/questions/get-webwork-code-from-file-path", ['file_path' => 'some path'])
            ->assertJson(['message' => 'You are not allowed to get the weBWork code.']);

    }

    /** @test */
    public function question_must_be_webwork_question()
    {
        $this->question->technology = 'not webwork';
        $this->question->save();
        $this->actingAs($this->user)->getJson("/api/questions/export-webwork-code/{$this->question->id}")
            ->assertJson(['error' => 'This is not a weBWork question.']);

    }

    /** @test */
    public function non_instructor_cannot_export_webwork_code()
    {
        $this->user->role = 3;
        $this->user->save();
        $this->actingAs($this->user)->getJson("/api/questions/export-webwork-code/{$this->question->id}")
            ->assertJson(['message' => 'You are not allowed to export the weBWork code.']);

    }


}
