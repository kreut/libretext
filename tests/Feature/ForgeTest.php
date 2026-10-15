<?php

namespace Tests\Feature;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Course;
use App\Enrollment;
use App\ForgeAssignmentQuestion;
use App\Question;
use App\Section;
use App\SubmissionFile;
use App\Traits\Test;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use function factory;

class ForgeTest extends TestCase
{
    use Test;

    private $user;
    private $student_user;
    private $non_owner_user;
    private $non_enrolled_student;
    private $course;
    private $section;
    private $assignment;
    private $question;
    private $assignment_question_id;

    public function setup(): void
    {
        parent::setUp();

        // Course owner (instructor)
        $this->user = factory(User::class)->create(['role' => 2]);
        $this->user->central_identity_id = 'test-uuid-instructor';
        $this->user->save();

        // Enrolled student
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->student_user->central_identity_id = 'test-uuid-student';
        $this->student_user->save();

        // Non-owner instructor
        $this->non_owner_user = factory(User::class)->create(['role' => 2]);
        $this->non_owner_user->central_identity_id = 'test-uuid-non-owner';
        $this->non_owner_user->save();

        // Non-enrolled student
        $this->non_enrolled_student = factory(User::class)->create(['role' => 3]);
        $this->non_enrolled_student->central_identity_id = 'test-uuid-non-enrolled';
        $this->non_enrolled_student->save();

        // Create course owned by $this->user
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);

        // Create section
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);

        // Enroll student in course
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        // Create assignment
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

        // Assign student to assignment
        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id, $this->student_user->id);

        // Create question
        $this->question = factory(Question::class)->create([
            'library' => 'adapt',
            'qti_json_type' => 'forge'
        ]);

        // Add question to assignment
        $this->assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none',
            'forge_settings' => json_encode([
                'drafts' => [
                    [
                        'uuid' => 'test-draft-uuid',
                        'title' => 'Draft 1',
                        'isFinal' => false,
                        'assign_tos' => []
                    ]
                ],
                'settings' => []
            ])
        ]);

        // Create forge assignment question
        ForgeAssignmentQuestion::create([
            'adapt_assignment_id' => $this->assignment->id,
            'adapt_question_id' => $this->question->id,
            'forge_question_id' => 'forge-question-id-123',
            'forge_class_id' => 'forge-class-id-123'
        ]);

        // Create assignment_question_forge_draft entry
        DB::table('assignment_question_forge_draft')->insert([
            'assignment_question_id' => $this->assignment_question_id,
            'forge_draft_id' => 'test-draft-uuid',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('key_secrets')->insert([
            'key' => 'forge',
            'secret' => 'test-forge-secret-key',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /** @test */
    public function need_valid_bearer_token_to_check_grader_access()
    {
        $this->getJson("/api/forge/grader-has-access/forge-draft-id/test-draft-uuid/grader/some-grader-uuid/student/some-student-uuid")
            ->assertJson(['message' => 'We were unable to validate whether the grader with UUID some-grader-uuid has access to the forge draft with draft ID test-draft-uuid submitted by the student with UUID some-student-uuid: Invalid Bearer token.']);
    }

    /** @test */
    public function grader_access_fails_with_invalid_forge_draft_id()
    {
        $secret = DB::table('key_secrets')->where('key', 'forge')->first()->secret;

        $this->withHeaders(['Authorization' => "Bearer $secret"])
            ->getJson("/api/forge/grader-has-access/forge-draft-id/nonexistent-draft-id/grader/{$this->user->central_identity_id}/student/{$this->student_user->central_identity_id}")
            ->assertJson(['message' => 'There is no Forge assignment question with Forge draft ID nonexistent-draft-id.']);
    }

    /** @test */
    public function grader_access_fails_with_nonexistent_student()
    {
        $secret = DB::table('key_secrets')->where('key', 'forge')->first()->secret;

        $this->withHeaders(['Authorization' => "Bearer $secret"])
            ->getJson("/api/forge/grader-has-access/forge-draft-id/test-draft-uuid/grader/{$this->user->central_identity_id}/student/nonexistent-student-uuid")
            ->assertJson(['message' => 'There is no student with UUID nonexistent-student-uuid.']);
    }

    /** @test */
    public function grader_access_fails_with_nonexistent_grader()
    {
        $secret = DB::table('key_secrets')->where('key', 'forge')->first()->secret;

        $this->withHeaders(['Authorization' => "Bearer $secret"])
            ->getJson("/api/forge/grader-has-access/forge-draft-id/test-draft-uuid/grader/nonexistent-grader-uuid/student/{$this->student_user->central_identity_id}")
            ->assertJson(['message' => 'There is no grader with UUID nonexistent-grader-uuid.']);
    }

    /** @test */
    public function grader_access_fails_when_grader_not_assigned_to_student_section()
    {
        $secret = DB::table('key_secrets')->where('key', 'forge')->first()->secret;

        $grader_user = factory(User::class)->create(['role' => 4]);
        $grader_user->central_identity_id = 'test-uuid-grader-no-section';
        $grader_user->save();

        $this->withHeaders(['Authorization' => "Bearer $secret"])
            ->getJson("/api/forge/grader-has-access/forge-draft-id/test-draft-uuid/grader/{$grader_user->central_identity_id}/student/{$this->student_user->central_identity_id}")
            ->assertJson(['message' => "The grader with UUID {$grader_user->central_identity_id} is not a grader for the student's section."]);
    }

    /** @test */
    public function grader_with_section_access_can_access_forge_draft()
    {
        $secret = DB::table('key_secrets')->where('key', 'forge')->first()->secret;

        $grader_user = factory(User::class)->create(['role' => 4]);
        $grader_user->central_identity_id = 'test-uuid-valid-grader';
        $grader_user->save();

        DB::table('graders')->insert([
            'user_id' => $grader_user->id,
            'section_id' => $this->section->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->withHeaders(['Authorization' => "Bearer $secret"])
            ->getJson("/api/forge/grader-has-access/forge-draft-id/test-draft-uuid/grader/{$grader_user->central_identity_id}/student/{$this->student_user->central_identity_id}")
            ->assertJson(['type' => 'success']);
    }


    /** @test */
    public function bearer_token_must_be_valid()
    {

        $this->withHeaders(['Authorization' => "Bearer some-dumb-secret"])
            ->postJson("/api/forge/submissions/forge-draft-id/test-draft-uuid/user/{$this->non_enrolled_student->central_identity_id}")
            ->assertJson(['message' => "Invalid Bearer token"]);
    }


    /** @test */
    public function student_not_enrolled_cannot_have_submission_stored()
    {
        $secret = DB::table('key_secrets')
            ->where('key', 'forge')
            ->first()
            ->secret;

        $this->withHeaders(['Authorization' => "Bearer $secret"])
            ->postJson("/api/forge/submissions/forge-draft-id/test-draft-uuid/user/{$this->non_enrolled_student->central_identity_id}")
            ->assertJson(['message' => "The student with UUID {$this->non_enrolled_student->central_identity_id} is not enrolled in this course."]);
    }
    /** @test */
    public function non_course_owner_cannot_get_drafts()
    {
        $this->actingAs($this->non_owner_user)
            ->getJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}/forge-draft-submissions")
            ->assertJson(['message' => 'You are not allowed to view submissions for this draft.']);
    }


    /** @test */
    public function non_course_owner_cannot_update_forge_settings()
    {
        $this->actingAs($this->non_owner_user)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/forge-settings", $this->getValidForgeSettingsData())
            ->assertJson(['message' => 'You are not allowed to update the Forge settings for that question.']);
    }


    private function getValidForgeSettingsData(): array
    {
        return [
            'drafts' => [
                [
                    'uuid' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                    'title' => 'Final Submission',
                    'isFinal' => true,
                    'late_policy' => 'not accepted',
                    'assign_tos' => [
                        [
                            'groups' => [['text' => 'Everybody', 'value' => ['course_id' => $this->course->id]]],
                            'available_from_date' => '2025-01-01',
                            'available_from_time' => '9:00 AM',
                            'due_date' => '2025-12-31',
                            'due_time' => '11:59 PM'
                        ]
                    ]
                ]
            ],
            'settings' => [
                'autoSubmission' => false,
                'preventAfterDueDate' => false,
                'autoAccept' => false,
                'showAnalytics' => 'never',
                'mainFileType' => 'document',
                'allowImport' => false,
                'additionalFiles' => [],
                'uploadFile' => false
            ]
        ];
    }

    /** @test */
    public function non_course_owner_cannot_get_forge_settings()
    {
        $this->actingAs($this->non_owner_user)
            ->getJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/forge-settings")
            ->assertJson(['message' => 'You are not allowed to get the Forge settings for that question.']);
    }


    /** @test */
    public function non_course_owner_cannot_allow_resubmission()
    {
        // Create a submission file for the student
        SubmissionFile::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student_user->id,
            'date_submitted' => now(),
            'type' => 'forge',
            'original_filename' => '',
            'submission' => '',
            'upload_count' => 1
        ]);

        $this->actingAs($this->non_owner_user)
            ->postJson("/api/forge/submissions/assignment/{$this->assignment->id}/question/{$this->question->id}/student/{$this->student_user->id}/allow-resubmission")
            ->assertJson(['message' => 'You are not allowed to allow a resubmission for this student.']);
    }


    /** @test */
    public function non_course_owner_cannot_get_submission_by_assignment_question_student()
    {
        $this->actingAs($this->non_owner_user)
            ->getJson("/api/forge/submissions/assignment/{$this->assignment->id}/question/{$this->question->id}/student/{$this->student_user->id}")
            ->assertJson(['message' => 'You are not a grader and do not own that course so cannot get submissions by assignment-question-student.']);
    }


    /** @test */
    public function non_enrolled_student_cannot_get_drafts_by_logged_in_user()
    {
        // Create a draft question for the test
        $draft_question = factory(Question::class)->create([
            'library' => 'adapt',
            'qti_json_type' => 'forge_iteration',
            'forge_source_id' => $this->question->id
        ]);

        $draft_aq_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $draft_question->id,
            'points' => 10,
            'order' => 2,
            'open_ended_submission_type' => 'none'
        ]);

        DB::table('assignment_question_forge_draft')->insert([
            'assignment_question_id' => $draft_aq_id,
            'forge_draft_id' => 'draft-uuid-for-test',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->actingAs($this->non_enrolled_student)
            ->getJson("/api/forge/assignments/{$this->assignment->id}/questions/{$this->question->id}/current-question/{$draft_question->id}")
            ->assertJson(['message' => 'You cannot get the assign tos by assignment-question-logged-in-user.']);
    }


    /** @test */
    public function non_instructor_and_non_student_cannot_initialize()
    {
        // Create a user with role 4 (grader) who isn't a grader for this course
        $grader_user = factory(User::class)->create(['role' => 4]);
        $grader_user->central_identity_id = 'test-uuid-grader';
        $grader_user->save();

        $this->actingAs($grader_user)
            ->postJson("/api/forge/assignment/{$this->assignment->id}/question/{$this->question->id}/initialize", [
                'user_id' => $grader_user->id
            ])
            ->assertJson(['message' => 'You cannot initialize the Forge settings.']);
    }

    /** @test */
    public function non_owner_instructor_cannot_initialize()
    {
        $this->actingAs($this->non_owner_user)
            ->postJson("/api/forge/assignment/{$this->assignment->id}/question/{$this->question->id}/initialize", [
                'user_id' => $this->non_owner_user->id
            ])
            ->assertJson(['message' => 'You cannot initialize the Forge settings since you do not own this course.']);
    }

    /** @test */
    public function non_enrolled_student_cannot_initialize()
    {
        $this->actingAs($this->non_enrolled_student)
            ->postJson("/api/forge/assignment/{$this->assignment->id}/question/{$this->question->id}/initialize", [
                'user_id' => $this->non_enrolled_student->id
            ])
            ->assertJson(['message' => 'You are not enrolled in this course so you cannot initialize the Forge settings.']);
    }

    /** @test */
    public function need_valid_bearer_token_to_get_user()
    {
        $this->getJson("/api/forge/user/{$this->user->central_identity_id}")
            ->assertJson(['message' => 'Invalid Bearer token']);
    }

    /** @test */
    public function valid_bearer_token_can_get_user()
    {
        $secret = DB::table('key_secrets')
            ->where('key', 'forge')
            ->first()
            ->secret;

        $this->withHeaders(['Authorization' => "Bearer $secret"])
            ->getJson("/api/forge/user/{$this->user->central_identity_id}")
            ->assertJson([
                'type' => 'success',
                'firstName' => $this->user->first_name,
                'lastName' => $this->user->last_name,
                'email' => $this->user->email
            ]);
    }

    /** @test */
    public function need_valid_bearer_token_to_get_course()
    {
        $this->getJson("/api/forge/course/{$this->course->id}")
            ->assertJson(['message' => 'Invalid Bearer token']);
    }

    /** @test */
    public function valid_bearer_token_can_get_course()
    {
        $secret = DB::table('key_secrets')
            ->where('key', 'forge')
            ->first()
            ->secret;

        $this->withHeaders(['Authorization' => "Bearer $secret"])
            ->getJson("/api/forge/course/{$this->course->id}")
            ->assertJson([
                'type' => 'success',
                'title' => $this->course->name
            ]);
    }

    /** @test */
    public function need_valid_bearer_token_to_store_submission()
    {
        $this->postJson("/api/forge/submissions/forge-draft-id/test-draft-uuid/user/{$this->student_user->central_identity_id}")
            ->assertJson(['message' => 'Invalid Bearer token']);
    }

    /*
    |--------------------------------------------------------------------------
    | Forge draft ids must belong to exactly one assignment
    |--------------------------------------------------------------------------
    | A draft id shared by two assignments made validateForgeQuestionAccess()
    | resolve students to the wrong course ("not enrolled" + question closed).
    */

    //UpdateForgeSettings requires draft ids to be valid UUIDs
    private const SHARED_DRAFT_UUID = '11111111-1111-4111-8111-111111111111';
    private const SAME_DRAFT_UUID = '22222222-2222-4222-8222-222222222222';
    private const FINAL_DRAFT_UUID = '33333333-3333-4333-8333-333333333333';
    private const COPIED_DRAFT_UUID = '44444444-4444-4444-8444-444444444444';
    private const SOURCE_DRAFT_UUID = '55555555-5555-4555-8555-555555555555';

    //update to match the remix route in routes/api.php
    private const REMIX_URL = '/api/assignments/%d/remix-assignment-with-chosen-questions';

    /**
     * Another course owned by the same instructor, with an assignment whose Forge
     * question already uses $forge_draft_id (the state a copied question used to create).
     */
    private function createOtherAssignmentUsingDraftId(string $forge_draft_id): Assignment
    {
        $other_course = factory(Course::class)->create(['user_id' => $this->user->id, 'name' => 'Other Course']);
        factory(Section::class)->create(['course_id' => $other_course->id]);
        $other_assignment = factory(Assignment::class)->create(['course_id' => $other_course->id, 'name' => 'Other Assignment']);
        $other_assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $other_assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);
        DB::table('assignment_question_forge_draft')->insert([
            'assignment_question_id' => $other_assignment_question_id,
            'forge_draft_id' => $forge_draft_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        return $other_assignment;
    }

    private function draft(string $uuid, string $title, bool $is_final, ?int $question_id = null): array
    {
        $draft = [
            'uuid' => $uuid,
            'title' => $title,
            'isFinal' => $is_final,
            'late_policy' => 'not accepted',
            'late_deduction_percent' => null,
            'late_deduction_applied_once' => null,
            'late_deduction_application_period' => null,
            'extensions' => [],
            'assign_tos' => [
                [
                    'groups' => [['text' => 'Everybody', 'value' => ['course_id' => $this->course->id]]],
                    'available_from_date' => '2025-01-01',
                    'available_from_time' => '9:00 AM',
                    'due_date' => '2025-12-31',
                    'due_time' => '11:59 PM'
                ]
            ]
        ];
        if ($question_id) {
            $draft['question_id'] = $question_id;
        }
        return $draft;
    }

    private function forgeSettingsWithDrafts(array $drafts): array
    {
        $data = $this->getValidForgeSettingsData();
        $data['drafts'] = $drafts;
        return $data;
    }

    /** @test */
    public function cannot_save_forge_settings_with_a_draft_id_used_in_another_assignment()
    {
        $this->createOtherAssignmentUsingDraftId(self::SHARED_DRAFT_UUID);

        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/forge-settings",
                $this->forgeSettingsWithDrafts([$this->draft(self::SHARED_DRAFT_UUID, 'Final Submission', true)]))
            ->assertJson([
                'type' => 'error',
                'message' => 'These Forge settings were copied from "Other Assignment" in "Other Course" and are still tied to that assignment, so they can\'t be saved here.  Please contact us for assistance.'
            ]);

        $this->assertEquals(1, DB::table('assignment_question_forge_draft')
            ->where('forge_draft_id', self::SHARED_DRAFT_UUID)
            ->count());
        $this->assertDatabaseHas('assignment_question_forge_draft', [
            'assignment_question_id' => $this->assignment_question_id,
            'forge_draft_id' => 'test-draft-uuid'
        ]);
    }

    /** @test */
    public function cannot_save_forge_settings_with_the_same_draft_id_twice()
    {
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/forge-settings",
                $this->forgeSettingsWithDrafts([
                    $this->draft(self::SAME_DRAFT_UUID, 'Draft 1', false),
                    $this->draft(self::SAME_DRAFT_UUID, 'Final Submission', true)
                ]))
            ->assertJson([
                'type' => 'error',
                'message' => 'Two of the drafts have the same ID.  Please refresh the page and try again.'
            ]);
    }

    /** @test */
    public function can_save_forge_settings_with_a_draft_id_only_used_in_this_assignment()
    {
        Http::fake(['*' => Http::response(['type' => 'success'], 200)]);

        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/forge-settings",
                $this->forgeSettingsWithDrafts([$this->draft(self::FINAL_DRAFT_UUID, 'Final Submission', true)]))
            ->assertJson(['type' => 'success']);

        $this->assertDatabaseHas('assignment_question_forge_draft', [
            'assignment_question_id' => $this->assignment_question_id,
            'forge_draft_id' => self::FINAL_DRAFT_UUID
        ]);
    }

    /** @test */
    public function saving_copied_forge_settings_does_not_change_another_assignments_draft_question()
    {
        Http::fake(['*' => Http::response(['type' => 'success'], 200)]);

        //a draft question that lives in another assignment
        $other_assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $other_draft_question = factory(Question::class)->create([
            'library' => 'adapt',
            'qti_json_type' => 'forge_iteration',
            'forge_source_id' => $this->question->id,
            'title' => 'Other Assignment Draft'
        ]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $other_assignment->id,
            'question_id' => $other_draft_question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);

        //this assignment's settings point at it, as copied settings used to
        DB::table('assignment_question')
            ->where('id', $this->assignment_question_id)
            ->update(['forge_settings' => json_encode([
                'drafts' => [
                    $this->draft(self::COPIED_DRAFT_UUID, 'Draft 1', false, $other_draft_question->id),
                    $this->draft(self::FINAL_DRAFT_UUID, 'Final Submission', true)
                ],
                'settings' => []
            ])]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/forge-settings",
                $this->forgeSettingsWithDrafts([
                    $this->draft(self::COPIED_DRAFT_UUID, 'Renamed Draft', false, $other_draft_question->id),
                    $this->draft(self::FINAL_DRAFT_UUID, 'Final Submission', true)
                ]))
            ->assertJson(['type' => 'success']);

        $this->assertEquals('Other Assignment Draft', $other_draft_question->fresh()->title);
        $this->assertNotEquals($other_draft_question->id, $response->json('drafts.0.question_id'));
        $this->assertDatabaseMissing('assignment_question', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $other_draft_question->id
        ]);
    }

    /** @test */
    public function remixing_a_forge_question_from_my_courses_does_not_copy_its_drafts()
    {
        $source_assignment = $this->createOtherAssignmentUsingDraftId(self::SOURCE_DRAFT_UUID);
        DB::table('assignment_question')
            ->where('assignment_id', $source_assignment->id)
            ->where('question_id', $this->question->id)
            ->update(['forge_settings' => json_encode([
                'drafts' => [$this->draft(self::SOURCE_DRAFT_UUID, 'Final Submission', true)],
                'settings' => []
            ])]);
        $target_assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

        $this->actingAs($this->user)
            ->patchJson(sprintf(self::REMIX_URL, $target_assignment->id), [
                'question_source' => 'my_courses',
                'chosen_questions' => [
                    ['assignment_id' => $source_assignment->id, 'question_id' => $this->question->id]
                ]
            ])
            ->assertJson(['type' => 'success']);

        $copied_assignment_question = DB::table('assignment_question')
            ->where('assignment_id', $target_assignment->id)
            ->where('question_id', $this->question->id)
            ->first();
        $this->assertNotNull($copied_assignment_question);
        $this->assertNull($copied_assignment_question->forge_settings);
        $this->assertDatabaseMissing('assignment_question_forge_draft', [
            'assignment_question_id' => $copied_assignment_question->id
        ]);
    }

    /** @test */
    public function cannot_remix_a_forge_draft_question_on_its_own()
    {
        $source_assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $draft_question = factory(Question::class)->create([
            'library' => 'adapt',
            'qti_json_type' => 'forge_iteration',
            'forge_source_id' => $this->question->id,
            'title' => 'Some Draft'
        ]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $source_assignment->id,
            'question_id' => $draft_question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 'none'
        ]);
        $target_assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

        $this->actingAs($this->user)
            ->patchJson(sprintf(self::REMIX_URL, $target_assignment->id), [
                'question_source' => 'my_courses',
                'chosen_questions' => [
                    ['assignment_id' => $source_assignment->id, 'question_id' => $draft_question->id]
                ]
            ])
            ->assertJson([
                'type' => 'error',
                'message' => '"Some Draft" is a Forge draft. Please add its main Forge question instead.'
            ]);

        $this->assertDatabaseMissing('assignment_question', [
            'assignment_id' => $target_assignment->id,
            'question_id' => $draft_question->id
        ]);
    }



}
