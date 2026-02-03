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


}
