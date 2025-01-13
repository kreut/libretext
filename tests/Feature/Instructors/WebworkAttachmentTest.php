<?php

namespace Tests\Feature\Instructors;

use App\Question;
use App\User;
use Tests\TestCase;

class WebworkAttachmentTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->admin_user = factory(User::class)->create(['email' => 'me@me.com']);
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->default_question_editor_user = factory(User::class)->create(['role' => 5, 'first_name' => 'Default Non-Instructor Editor']);
        $this->question_editor_user = factory(User::class)->create(['role' => 5]);
        $this->question = factory(Question::class)->create(['library' => 'adapt']);

    }

    /** @test */
    public function non_owner_cannot_destroy_webwork_attachment()
    {
        $this->question->question_editor_user_id = $this->question_editor_user->id;
        $question_owner = User::find($this->question_editor_user->id);
        $this->question->save();
        $this->actingAs($this->student_user)
            ->postJson("/api/webwork-attachments/destroy", [
                'webwork_attachment' => ['filename' => 'some file', 'status' => 'attached'],
                'question_id' => $this->question->id
            ])
            ->assertJson(['message' => "This is not your question to edit. This question is owned by $question_owner->first_name $question_owner->last_name."]);

    }

    /** @test */
    public function non_instructor_cannot_upload_webwork_attachments()
    {
        $this->actingAs($this->student_user)
            ->putJson("/api/webwork-attachments/upload")
            ->assertJson(['message' => 'You are not allowed to upload webwork attachments.']);

    }


    /** @test */
    public function non_owner_cannot_get_webwork_attachments_by_question()
    {
        $this->question->question_editor_user_id = $this->question_editor_user->id;
        $question_owner = User::find($this->question_editor_user->id);
        $this->question->save();
        $this->actingAs($this->student_user)
            ->getJson("/api/webwork-attachments/question/{$this->question->id}/0")
            ->assertJson(['message' => "This is not your question to edit. This question is owned by $question_owner->first_name $question_owner->last_name."]);

    }

}
