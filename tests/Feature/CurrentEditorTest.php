<?php

namespace Tests\Feature;

use App\Question;
use App\Traits\DateFormatter;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use function factory;

class CurrentEditorTest extends TestCase
{
    use DateFormatter;

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->non_instructor_question_editor = factory(User::class)->create(['role' => 5]);
        $this->non_instructor_question_editor_2 = factory(User::class)->create(['role' => 5]);
        $this->question = factory(Question::class)->create(['page_id' => 2348783]);

    }

    /** @test */
    public function non_instructor_question_editor_cannot_update_a_current_question_editor()
    {

        $this->actingAs($this->user)->patchJson("/api/current-question-editor/{$this->question->id}")
            ->assertJson(['message' => 'You are not allowed to update the current question editor.']);

    }

    /** @test */
    public function non_instructor_question_editor_can_update_a_current_question_editor()
    {
        $this->actingAs($this->non_instructor_question_editor)
            ->patchJson("/api/current-question-editor/{$this->question->id}")
            ->assertJson(['type' => 'success']);

        $this->assertDatabaseHas('current_question_editors', [
            'user_id' => $this->non_instructor_question_editor->id,
            'question_id' => $this->question->id]);

    }

    /** @test */
    public function if_non_instructor_question_editor_began_editing_less_than_24_hours_ago_new_non_instructor_question_editor_will_receive_a_message()
    {
        $this->actingAs($this->non_instructor_question_editor)
            ->patchJson("/api/current-question-editor/{$this->question->id}")
            ->assertJson(['type' => 'success']);
        $now = Carbon::now()->subHour();
        DB::table('current_question_editors')->insert(['user_id' => $this->non_instructor_question_editor->id,
            'question_id' => $this->question->id,
            'created_at' => $now]);
        $formatted_start_time = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($now, $this->non_instructor_question_editor_2->time_zone, 'F jS \a\t g:i a');
        $current_question_editor = "{$this->non_instructor_question_editor->first_name} {$this->non_instructor_question_editor->last_name} began editing this question on $formatted_start_time. Please hold off on editing the question until they have completed their work.";
        $this->actingAs($this->non_instructor_question_editor_2)
            ->getJson("/api/current-question-editor/{$this->question->id}")
            ->assertJson(['current_question_editor' => $current_question_editor]);

    }

    /** @test */
    public function non_instructor_question_editor_cannot_have_current_question_editor_deleted()
    {
        $this->actingAs($this->user)->deleteJson("/api/current-question-editor/{$this->question->id}")
            ->assertJson(['message' => 'You are not allowed to remove the current question editor.']);

    }

    /** @test */
    public function instructor_editor_can_have_current_question_editor_deleted()
    {
        DB::table('current_question_editors')->insert(['user_id' => $this->non_instructor_question_editor->id,
            'question_id' => $this->question->id]);
        $this->assertDatabaseHas('current_question_editors', [
            'user_id' => $this->non_instructor_question_editor->id,
            'question_id' => $this->question->id]);

        $this->actingAs($this->non_instructor_question_editor)->deleteJson("/api/current-question-editor/{$this->question->id}")
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseCount('current_question_editors', 0);
    }
}
