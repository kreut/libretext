<?php

namespace Tests\Feature\Instructors;


use App\Assignment;
use App\AssignmentSyncQuestion;
use App\AssignToTiming;
use App\Course;
use App\Enrollment;
use App\Question;
use App\QuestionRevision;
use App\SavedQuestionsFolder;
use App\Section;
use App\Submission;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class QuestionRevisionsTest extends TestCase
{

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create(['id' => 1]);
        //create a student and enroll in the class
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->question = factory(Question::class)->create(['page_id' => 17652]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 0
        ]);
        $this->question_revision = factory(QuestionRevision::class)->create(['action' => 'notify',
            'question_id' => $this->question->id]);
        DB::table('pending_question_revisions')
            ->insert(['assignment_id' => $this->assignment->id,
                'question_id' => $this->question->id,
                'question_revision_id' => $this->question_revision->id]);

    }

    private function _getQuestionInfo($user_id): array
    {
        $saved_questions_folder = factory(SavedQuestionsFolder::class)->create(['user_id' => $user_id, 'type' => 'my_questions']);
        $question_info = $this->question->toArray();
        $question_info['folder_id'] = $saved_questions_folder->id;
        $question_info['public'] = 1;
        $question_info['author'] = $question_info['title'] = 'sdfdsf';
        $question_info['license'] = 'arr';
        $question_info['tags'] = [];
        $question_info['revision_action'] = 'notify';
        $question_info['reason_for_edit'] = 'blah blah blah';
        return $question_info;
    }


    /** @test */
    public function if_a_course_has_auto_update_and_no_enrollments_and_the_owner_does_not_request_an_update_the_question_is_not_automatically_updated()
    {

        $user_2 = factory(User::class)->create();
        $this->question->question_editor_user_id = $user_2->id;
        $this->question->save();
        $course_2 = factory(Course::class)->create(['user_id' => $user_2->id, 'auto_update_question_revisions' => 1]);
        $assignment = factory(Assignment::class)->create(['course_id' => $course_2->id]);
        $assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 0
        ]);
        $this->assertNull(AssignmentSyncQuestion::find($assignment_question_id)->question_revision_id);

        //make the assignment open
        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $assignment->id;
        $assignToTiming->available_from = Carbon::now()->subDays(3);
        $assignToTiming->due = Carbon::now()->addDays(2);
        $assignToTiming->save();


        $question_info = $this->_getQuestionInfo($user_2->id);
        $question_info['automatically_update_revision'] = "0";
        $question_info['revision_action'] = 'notify';
        $question_info['question_type'] = 'assessment';
        $question_info['technology'] = 'h5p';
        $question_info['technology_id'] = 90;
        $question_info['test'] = true;
        $this->actingAs($user_2)->patchJson("/api/questions/{$this->question->id}",
            $question_info)
            ->assertJson(['type' => 'success']);

        $non_updated_question_revision_id = DB::table('question_revisions')
            ->where('question_id', $this->question->id)
            ->orderBy('id')
            ->first()
            ->id;
        $this->assertDatabaseHas('assignment_question', ['id' => $assignment_question_id, 'question_revision_id' => $non_updated_question_revision_id]);

    }


    /** @test */
    public function if_a_course_has_auto_update_and_no_enrollments_the_question_is_automatically_updated()
    {

        $user_2 = factory(User::class)->create();
        $course_2 = factory(Course::class)->create(['user_id' => $user_2->id, 'auto_update_question_revisions' => 1]);
        $assignment = factory(Assignment::class)->create(['course_id' => $course_2->id]);
        $assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 0
        ]);
        $this->assertNull(AssignmentSyncQuestion::find($assignment_question_id)->question_revision_id);

        $question_info = $this->_getQuestionInfo($user_2->id);
        $question_info['automatically_update_revision'] = "1";
        $question_info['revision_action'] = 'notify';
        $question_info['question_type'] = 'assessment';
        $question_info['technology'] = 'h5p';
        $question_info['technology_id'] = 90;
        $this->actingAs($this->user)->patchJson("/api/questions/{$this->question->id}",
            $question_info)
            ->assertJson(['type' => 'success']);

        $latest_question_revision_id = DB::table('question_revisions')
            ->where('question_id', $this->question->id)
            ->orderBy('id', 'desc')
            ->first()
            ->id;
        $this->assertDatabaseHas('assignment_question', ['id' => $assignment_question_id, 'question_revision_id' => $latest_question_revision_id]);

    }


    /** @test */
    public function if_a_course_has_auto_update_and_enrollments_the_question_is_not_automatically_updated()
    {

        $user_2 = factory(User::class)->create();
        $course_2 = factory(Course::class)->create(['user_id' => $user_2->id, 'auto_update_question_revisions' => 1]);
        $section = factory(Section::class)->create(['course_id' => $course_2->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $section->id,
            'course_id' => $course_2->id
        ]);
        $this->assertCount(1, Enrollment::where('course_id', $course_2->id)->get());
        $assignment = factory(Assignment::class)->create(['course_id' => $course_2->id]);
        $assignment_question_id = DB::table('assignment_question')->insertGetId([
            'assignment_id' => $assignment->id,
            'question_id' => $this->question->id,
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => 0
        ]);

        $question_info = $this->_getQuestionInfo($user_2->id);
        $question_info['automatically_update_revision'] = "1";
        $question_info['revision_action'] = 'notify';
        $question_info['question_type'] = 'assessment';
        $question_info['technology'] = 'h5p';
        $question_info['technology_id'] = 90;
        $this->assertNull(AssignmentSyncQuestion::find($assignment_question_id)->question_revision_id);
        $this->actingAs($this->user)->patchJson("/api/questions/{$this->question->id}",
            $question_info)
            ->assertJson(['type' => 'success']);

        //should be the first question revision since it won't get updated
        $expected_question_revision = DB::table('question_revisions')->where('question_id', $this->question->id)->orderBy('id')->first();
        $this->assertEquals($expected_question_revision->id, AssignmentSyncQuestion::find($assignment_question_id)->question_revision_id);

    }


    /** @test */
    public function correct_webwork_dir_is_created()
    {
        $question_info = $this->_getQuestionInfo($this->user->id);
        $question_info['question_type'] = 'assessment';
        $question_info['revision_action'] = 'propagate';
        $question_info['changes_are_topical'] = true;
        $question_info['technology'] = 'webwork';
        $question_info['webwork_code'] = 'some code';
        $question_info['new_auto_graded_code'] = 'webwork';
        $question_info['check_webwork_dir'] = true;
        $question_info['automatically_update_revision'] = "0";
        $webwork_dir = json_decode($this->actingAs($this->user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->patch("/api/questions/{$this->question->id}",
                $question_info)
            ->getContent(), 1)['webwork_dir'];
        $question_revision_id = QuestionRevision::where('question_id', $this->question->id)->orderBy('revision_number', 'DESC')->first()->id;
        $this->assertEquals("{$this->question->id}-$question_revision_id", $webwork_dir);
    }


    public function webwork_attachment_is_removed_from_the_correct_folder()
    {


    }

    /** @test */
    public function non_instructor_cannot_get_revision_info_for_a_question()
    {
        $this->question->question_editor_user_id = $this->student_user->id;
        $this->question->save();
        $this->actingAs($this->student_user)
            ->getJson("/api/question-revisions/question/{$this->question->id}")
            ->assertJson(['message' => 'You are not allowed to get the revisions for this question.']);

    }

    /** @test */
    public function non_instructor_cannot_get_update_revision_info_for_a_question()
    {

        $this->actingAs($this->student_user)
            ->getJson("/api/question-revisions/{$this->question_revision->id}/assignment/{$this->assignment->id}/question/{$this->question->id}/update-info")
            ->assertJson(['message' => 'You are not allowed to get the revision for this question.']);
    }

    /** @test */
    public function non_instructor_cannot_get_show_revision_info_for_a_question()
    {

        $this->actingAs($this->student_user)
            ->getJson("/api/question-revisions/{$this->question_revision->id}")
            ->assertJson(['message' => 'You are not allowed to get the revision for this question.']);

    }

    /** @test */
    public function non_power_user_cannot_propagate_if_there_are_differing_non_meta_properties()
    {
        $user = factory(User::class)->create(['id' => 3234]);
        $question_info = $this->_getQuestionInfo($user->id);
        $question_info['revision_action'] = 'propagate';
        $this->actingAs($user)->patchJson("/api/questions/{$this->question->id}",
            $question_info)
            ->assertJson(['message' => 'You cannot propagate the question revision since there are differing properties that are not topical in nature.']);
    }

    /** @test */
    public function must_state_whether_to_automatically_update_revision()
    {
        $question_info = $this->_getQuestionInfo($this->user->id);
        $question_info['reason_for_edit'] = 'sdfsdfsdfsdffs';
        $this->actingAs($this->user)->patchJson("/api/questions/{$this->question->id}",
            $question_info)
            ->assertJson(['automatically_update_revision_error' => 'Please specify whether you would like to automatically update this question in your current assignments.']);

    }

    /** @test */
    public function reason_for_edit_must_exist()
    {
        $question_info = $this->_getQuestionInfo($this->user->id);
        $question_info['reason_for_edit'] = null;
        $this->actingAs($this->user)->patchJson("/api/questions/{$this->question->id}",
            $question_info)
            ->assertJson(['reason_for_edit_error' => 'Please provide a reason for the edit.']);


    }

    /** @test */
    public function must_confirm_that_the_change_is_topical_for_save_and_propagate()
    {
        $question_info = $this->_getQuestionInfo($this->user->id);
        $question_info['revision_action'] = 'propagate';
        $this->actingAs($this->user)
            ->disableCookieEncryption()
            ->withCookie('IS_ME', env('IS_ME_COOKIE'))
            ->patch("/api/questions/{$this->question->id}",
                $question_info)
            ->assertJson(['changes_are_topical_error' => 'You must confirm that the changes are topical.']);


    }


    /** @test */
    public function non_instructor_cannot_update_to_latest_revision()
    {
        $this->actingAs($this->student_user)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/update-to-latest-revision")
            ->assertJson(['message' => "You are not allowed to update to the latest revision for that question."]);

    }

    /** @test */
    public function assignment_question_correctly_updated_to_most_latest_revision()
    {
        $assignment_question = AssignmentSyncQuestion::find($this->assignment_question_id);
        $this->assertEquals(0, $assignment_question->question_revision_id);
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/update-to-latest-revision",
                ['understand_student_submissions_removed' => true])
            ->assertJson(['type' => "success"]);
        $assignment_question = AssignmentSyncQuestion::find($this->assignment_question_id);
        $this->assertEquals($this->question_revision->id, $assignment_question->question_revision_id);
    }

    /** @test */
    public function scores_are_correctly_recomputed_for_save_and_notify_updates()
    {
        $original_assignment_score = 30;
        $question_score = 5;
        DB::table('scores')
            ->insert(['assignment_id' => $this->assignment->id,
                'user_id' => $this->student_user->id,
                'score' => $original_assignment_score]);
        Submission::create(['assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student_user->id,
            'score' => $question_score,
            'submission_count' => 1,
            'answered_correctly_at_least_once' => false,
            'submission' => 'some submission']);
        $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/update-to-latest-revision",
                ['understand_student_submissions_removed' => true])
            ->assertJson(['type' => "success"]);
        $score = DB::table('scores')->where('assignment_id', $this->assignment->id)
            ->where('user_id', $this->student_user->id)
            ->first();
        $this->assertEquals($original_assignment_score - $question_score, $score->score);
        $this->assertDatabaseCount('submissions', 0);
    }


}