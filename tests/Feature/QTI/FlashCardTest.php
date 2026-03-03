<?php

namespace Tests\Feature\QTI;

use App\Assignment;

use App\Course;
use App\Enrollment;
use App\Question;
use App\Section;
use App\Traits\Test;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FlashcardCardSettingsTest extends TestCase
{
    use Test;


    public function setUp(): void
    {
        parent::setUp();

        $this->instructor = factory(User::class)->create(['role' => 2]);
        $this->non_owner_instructor = factory(User::class)->create(['role' => 2]);
        $this->student = factory(User::class)->create(['role' => 3]);
        $this->non_enrolled_student = factory(User::class)->create(['role' => 3]);

        $this->course = factory(Course::class)->create(['user_id' => $this->instructor->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);

        factory(Enrollment::class)->create([
            'user_id' => $this->student->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
        ]);

        $this->default_flashcard_settings = [
            'autoplay'       => ['enabled' => false, 'seconds' => 4, 'student_override' => true],
            'random_shuffle' => ['enabled' => false, 'student_override' => true],
            'show_hint'      => ['enabled' => true,  'student_override' => true],
            'text_to_speech' => ['enabled' => false, 'student_override' => true],
            'captions'       => ['enabled' => false, 'student_override' => true],
        ];

        $this->assignment = factory(Assignment::class)->create([
            'course_id'          => $this->course->id,
            'assessment_type'    => 'flashcard',
            'flashcard_settings' => json_encode($this->default_flashcard_settings),
        ]);

        $this->assignUserToAssignment(
            $this->assignment->id, 'course',
            $this->course->id, $this->student->id
        );

        $this->question = factory(Question::class)->create([
            'library'       => 'adapt',
            'qti_json_type' => 'flashcard',
        ]);

        DB::table('assignment_question')->insert([
            'assignment_id'             => $this->assignment->id,
            'question_id'               => $this->question->id,
            'points'                    => 10,
            'order'                     => 1,
            'open_ended_submission_type'=> 'none',
        ]);
    }

    // ── getFlashcardCardSettings ────────────────────────────────────────────

    /** @test */
    public function instructor_can_get_flashcard_card_settings()
    {
        $this->actingAs($this->instructor)
            ->getJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/flashcard-card-settings")
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function enrolled_student_can_get_flashcard_card_settings()
    {
        $this->actingAs($this->student)
            ->getJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/flashcard-card-settings")
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function non_enrolled_student_cannot_get_flashcard_card_settings()
    {
        $this->actingAs($this->non_enrolled_student)
            ->getJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/flashcard-card-settings")
            ->assertJson(['type' => 'error']);
    }

    /** @test */
    public function non_owner_instructor_cannot_get_flashcard_card_settings()
    {
        $this->actingAs($this->non_owner_instructor)
            ->getJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/flashcard-card-settings")
            ->assertJson(['type' => 'error']);
    }

    /** @test */
    public function returns_null_when_no_card_level_override_exists()
    {
        // No flashcard_card_settings set on assignment_question row
        $response = $this->actingAs($this->instructor)
            ->getJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/flashcard-card-settings")
            ->assertJson(['type' => 'success'])
            ->json();

        // When no card-level override, falls back to assignment-level defaults
        $this->assertNotNull($response['flashcard_card_settings']);
        $this->assertArrayHasKey('show_hint', $response['flashcard_card_settings']);
        $this->assertArrayHasKey('text_to_speech', $response['flashcard_card_settings']);
        $this->assertArrayHasKey('captions', $response['flashcard_card_settings']);
    }

    /** @test */
    public function returns_saved_settings_when_card_level_override_exists()
    {
        $card_settings = [
            'show_hint'      => false,
            'text_to_speech' => true,
            'captions'       => false,
            'autoplay_seconds' => 6,
        ];

        DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->update(['flashcard_card_settings' => json_encode($card_settings)]);

        $response = $this->actingAs($this->instructor)
            ->getJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/flashcard-card-settings")
            ->assertJson(['type' => 'success'])
            ->json();

        $this->assertFalse($response['flashcard_card_settings']->show_hint ?? $response['flashcard_card_settings']['show_hint']);
        $this->assertTrue($response['flashcard_card_settings']->text_to_speech ?? $response['flashcard_card_settings']['text_to_speech']);
    }

    /** @test */
    public function assignment_level_defaults_are_returned_as_fallback()
    {
        // show_hint is enabled at the assignment level
        $response = $this->actingAs($this->instructor)
            ->getJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/flashcard-card-settings")
            ->assertJson(['type' => 'success'])
            ->json();

        $settings = $response['flashcard_card_settings'];
        // show_hint.enabled = true in default_flashcard_settings
        $show_hint = is_array($settings) ? $settings['show_hint'] : $settings->show_hint;
        $this->assertTrue($show_hint);
        // text_to_speech.enabled = false in default_flashcard_settings
        $tts = is_array($settings) ? $settings['text_to_speech'] : $settings->text_to_speech;
        $this->assertFalse($tts);
    }

    // ── updateFlashcardCardSettings ─────────────────────────────────────────

    /** @test */
    public function instructor_can_update_flashcard_card_settings()
    {
        $this->actingAs($this->instructor)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/flashcard-card-settings", [
                'show_hint'        => false,
                'text_to_speech'   => true,
                'captions'         => false,
                'autoplay_seconds' => null,
            ])
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function non_owner_instructor_cannot_update_flashcard_card_settings()
    {
        $this->actingAs($this->non_owner_instructor)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/flashcard-card-settings", [
                'show_hint'      => false,
                'text_to_speech' => true,
                'captions'       => false,
            ])
            ->assertJson(['message' => 'You are not allowed to update the flashcard settings for that question.']);
    }

    /** @test */
    public function student_cannot_update_flashcard_card_settings()
    {
        $this->actingAs($this->student)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/flashcard-card-settings", [
                'show_hint'      => false,
                'text_to_speech' => true,
                'captions'       => false,
            ])
            ->assertJson(['message' => 'You are not allowed to update the flashcard settings for that question.']);
    }

    /** @test */
    public function settings_are_persisted_after_update()
    {
        $payload = [
            'show_hint'        => false,
            'text_to_speech'   => true,
            'captions'         => true,
            'autoplay_seconds' => 8,
        ];

        $this->actingAs($this->instructor)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/flashcard-card-settings", $payload)
            ->assertJson(['type' => 'success']);

        $saved = DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->value('flashcard_card_settings');

        $decoded = json_decode($saved, true);
        $this->assertFalse($decoded['show_hint']);
        $this->assertTrue($decoded['text_to_speech']);
        $this->assertTrue($decoded['captions']);
        $this->assertEquals(8, $decoded['autoplay_seconds']);
    }

    /** @test */
    public function updated_settings_are_returned_on_subsequent_get()
    {
        $payload = [
            'show_hint'        => false,
            'text_to_speech'   => true,
            'captions'         => false,
            'autoplay_seconds' => null,
        ];

        $this->actingAs($this->instructor)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/flashcard-card-settings", $payload);

        $response = $this->actingAs($this->instructor)
            ->getJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/flashcard-card-settings")
            ->assertJson(['type' => 'success'])
            ->json();

        $settings = $response['flashcard_card_settings'];
        $show_hint = is_array($settings) ? $settings['show_hint'] : $settings->show_hint;
        $this->assertFalse($show_hint);
        $tts = is_array($settings) ? $settings['text_to_speech'] : $settings->text_to_speech;
        $this->assertTrue($tts);
    }
}
