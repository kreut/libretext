<?php

namespace Tests\Feature;

use App\Question;
use App\QuestionMediaUpload;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionMediaUploadTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->question = factory(Question::class)->create(['question_editor_user_id' => $this->user->id]);
        $this->questionMediaUpload = QuestionMediaUpload::create([
            'question_id' => $this->question->id,
            'original_filename' => 'some name',
            'size' => 200,
            's3_key' => 'some key',
            'transcript' => 'sdfdsf',
            'status' => 'completed'
        ]);
    }

    /** @test */
    public function non_owner_cannot_get_presigned_url()
    {
        $this->actingAs($this->user_2)
            ->postJson("/api/s3/pre-signed-url",
                ['upload_file_type' => 'vtt',
                    's3_key' => 'some-bad-key',
                    'file_name' => 'who_knows'
                ])
            ->assertJson(['message' => "You do not own that media so you cannot upload a transcript."]);
    }


    /** @test */
    public function non_owner_cannot_validate_vtt()
    {
        $this->actingAs($this->user_2)
            ->getJson("/api/question-media/validate-vtt/{$this->questionMediaUpload->id}")
            ->assertJson(['message' => "You are not allowed to validate the .vtt file."]);
    }

    /** @test */
    public function vtt_file_must_exist()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/question-media/{$this->questionMediaUpload->id}/caption/1")
            ->assertJson(['message' => "You are not allowed to update this transcript."]);
    }

    /** @test */
    public function non_owner_cannot_update_caption()
    {
        $this->actingAs($this->user_2)
            ->patchJson("/api/question-media/{$this->questionMediaUpload->id}/caption/1")
            ->assertJson(['message' => "You are not allowed to update this transcript."]);
    }

    /** @test */
    public function non_owner_cannot_delete_question_media_upload()
    {
        $this->actingAs($this->user_2)
            ->deleteJson("/api/question-media/{$this->questionMediaUpload->id}")
            ->assertJson(['message' => "You are not allowed to delete this question media."]);

    }


}