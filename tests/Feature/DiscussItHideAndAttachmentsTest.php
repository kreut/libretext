<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\DiscussionComment;
use App\DiscussionCommentAttachment;
use App\Enrollment;
use App\Question;
use App\QuestionMediaUpload;
use App\Section;
use App\User;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Discuss-it: "students must post before seeing classmates' comments", and files attached to comments.
 */
class DiscussItHideAndAttachmentsTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create(['role' => 3]);
        $this->student_user_2 = factory(User::class)->create(['role' => 3]);
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id, 'public' => 0]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        foreach ([$this->student_user, $this->student_user_2] as $student_user) {
            factory(Enrollment::class)->create([
                'user_id' => $student_user->id,
                'section_id' => $this->section->id,
                'course_id' => $this->course->id
            ]);
        }
        //formative assignments pass the general submission policy without assign-to timings
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id, 'formative' => 1]);
        $this->question = factory(Question::class)->create([
            'technology' => 'qti',
            'qti_json_type' => 'discuss_it',
            'qti_json' => json_encode(['questionType' => 'discuss_it',
                'prompt' => '<p>Discuss this.</p>',
                'media_uploads' => [],
                'jsonType' => 'question_json'])]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'discuss_it_settings' => json_encode($this->_settings()),
            'points' => 10,
            'order' => 1,
            'open_ended_submission_type' => '0'
        ]);
        $this->questionMediaUpload = QuestionMediaUpload::create([
            'question_id' => $this->question->id,
            'original_filename' => 'some name',
            'size' => 200,
            's3_key' => 'some key',
            'transcript' => 'sdfdsf',
            'status' => 'completed'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function _settings(array $overrides = []): array
    {
        return array_merge([
            'number_of_groups' => 1,
            'students_can_edit_comments' => '1',
            'students_can_delete_comments' => '1',
            'students_can_start_threads' => '1',
            'students_can_reply_to_threads' => '1',
            'students_must_post_before_viewing' => '1',
            'min_number_of_initiated_discussion_threads' => '1',
            'min_number_of_replies' => '0',
            'min_number_of_initiate_or_reply_in_threads' => '0',
            'min_number_of_words' => '3',
            'min_length_of_audio_video' => '',
            'response_modes' => ['text'],
            'students_can_attach_files' => '1',
            'allowed_file_types' => ['pdf', 'image', 'word', 'excel', 'powerpoint'],
            'min_number_of_attached_files' => '0',
            'auto_grade' => '0',
            'completion_criteria' => '1'], $overrides);
    }

    private function _setSettings(array $overrides): void
    {
        DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->update(['discuss_it_settings' => json_encode($this->_settings($overrides))]);
    }

    private function _saveSettings(array $overrides)
    {
        return $this->actingAs($this->user)
            ->patchJson("/api/assignments/{$this->assignment->id}/question/{$this->question->id}/discuss-it-settings", $this->_settings($overrides));
    }

    private function _thread(User $user, bool $satisfied_requirement = true): int
    {
        $discussion_id = DB::table('discussions')->insertGetId([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'media_upload_id' => $this->questionMediaUpload->id,
            'user_id' => $user->id,
            'group' => 1]);
        $this->_comment($discussion_id, $user, $satisfied_requirement);
        return $discussion_id;
    }

    private function _comment(int $discussion_id, User $user, bool $satisfied_requirement = true): DiscussionComment
    {
        return DiscussionComment::create([
            'discussion_id' => $discussion_id,
            'posted_in_assignment_id' => $this->assignment->id,
            'user_id' => $user->id,
            'text' => "a comment by user $user->id",
            'satisfied_requirement' => $satisfied_requirement ? 1 : 0]);
    }

    private function _discussionsAs(User $user)
    {
        return $this->actingAs($user)
            ->getJson("/api/discussions/assignment/{$this->assignment->id}/question/{$this->question->id}/media-upload/{$this->questionMediaUpload->id}")
            ->assertJson(['type' => 'success']);
    }

    private function _commentAuthorIds($response): array
    {
        $author_ids = [];
        foreach ($response->json('discussions') as $discussion) {
            foreach ($discussion['comments'] as $comment) {
                $author_ids[] = (int)$comment['created_by_user_id'];
            }
        }
        sort($author_ids);
        return $author_ids;
    }

    private function _post(User $user, int $discussion_id, array $comment)
    {
        return $this->actingAs($user)
            ->postJson("/api/discussions/assignment/{$this->assignment->id}/question/{$this->question->id}/{$this->questionMediaUpload->id}/$discussion_id/0", $comment);
    }

    private function _uploadedFile(string $filename, int $kilobytes = 10): void
    {
        //UploadedFile::fake()->create() only reports a size (the file itself is empty), so make a real file of
        //that size; ftruncate fills it without building the contents in memory
        $path = tempnam(sys_get_temp_dir(), 'discuss-it-file');
        $handle = fopen($path, 'w');
        ftruncate($handle, $kilobytes * 1024);
        fclose($handle);
        Storage::disk('s3')->putFileAs((new QuestionMediaUpload())->getDir(), new File($path), $filename);
        unlink($path);
    }

    /**
     * A text comment (long enough to meet the 3-word minimum) with these attachments: [stored name => original name]
     */
    private function _commentWithFiles(array $files, string $text = 'here is my work attached'): array
    {
        $attachments = [];
        foreach ($files as $file => $original_filename) {
            $attachments[] = ['file' => $file, 'original_filename' => $original_filename];
        }
        return ['type' => 'text', 'text' => $text, 'attachments' => $attachments];
    }

    private function _patchAttachments(DiscussionComment $discussionComment, array $keep_attachment_ids, array $files = [])
    {
        $attachments = [];
        foreach ($files as $file => $original_filename) {
            $attachments[] = ['file' => $file, 'original_filename' => $original_filename];
        }
        return $this->actingAs($this->student_user)
            ->patchJson("/api/discussion-comments/$discussionComment->id/attachments", [
                'keep_attachment_ids' => $keep_attachment_ids,
                'attachments' => $attachments,
                'viewing_assignment_id' => $this->assignment->id]);
    }

    private function _newestCommentBy(User $user): DiscussionComment
    {
        return DiscussionComment::where('user_id', $user->id)->orderByDesc('id')->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function hiding_comments_until_students_post_requires_starting_a_thread_in_the_completion_criteria()
    {
        $this->_saveSettings(['min_number_of_initiated_discussion_threads' => '0'])
            ->assertJsonValidationErrors('students_must_post_before_viewing');
        $this->_saveSettings(['completion_criteria' => '0'])
            ->assertJsonValidationErrors('students_must_post_before_viewing');
    }

    /** @test */
    public function hiding_comments_until_students_post_requires_that_students_can_start_threads()
    {
        $this->_saveSettings(['students_can_start_threads' => '0'])
            ->assertJsonValidationErrors('students_must_post_before_viewing');
    }

    /** @test */
    public function hiding_comments_until_students_post_can_be_saved_when_students_can_meet_it()
    {
        $this->_saveSettings([])->assertJson(['type' => 'success']);
        $settings = json_decode(DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->value('discuss_it_settings'), true);
        $this->assertEquals('1', $settings['students_must_post_before_viewing']);
    }

    /** @test */
    public function attaching_files_needs_at_least_one_valid_file_type()
    {
        $this->_saveSettings(['allowed_file_types' => []])
            ->assertJsonValidationErrors('allowed_file_types');
        $this->_saveSettings(['allowed_file_types' => ['exe']])
            ->assertJsonValidationErrors('allowed_file_types.0');
    }

    /** @test */
    public function requiring_attached_files_needs_attaching_turned_on()
    {
        $this->_saveSettings(['students_can_attach_files' => '0', 'min_number_of_attached_files' => '2'])
            ->assertJsonValidationErrors('min_number_of_attached_files');
        $this->_saveSettings(['min_number_of_attached_files' => '2'])
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function file_is_no_longer_a_response_mode()
    {
        //settings saved while "file" was a response mode still have it; it's dropped rather than refused
        $this->_saveSettings(['response_modes' => ['text', 'file']])
            ->assertJson(['type' => 'success']);
        $settings = json_decode(DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->value('discuss_it_settings'), true);
        $this->assertEquals(['text'], $settings['response_modes']);
    }
    /*
    |--------------------------------------------------------------------------
    | Hiding comments until the student posts
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function before_posting_a_student_only_sees_instructor_comments()
    {
        $instructor_thread_id = $this->_thread($this->user);
        $this->_comment($instructor_thread_id, $this->student_user_2);
        $this->_thread($this->student_user_2);

        $response = $this->_discussionsAs($this->student_user)
            ->assertJson(['comments_hidden_until_posted' => true]);
        $this->assertCount(1, $response->json('discussions'));
        $this->assertEquals([$this->user->id], $this->_commentAuthorIds($response));
    }

    /** @test */
    public function a_student_sees_everything_once_they_start_a_thread_that_meets_the_requirements()
    {
        $instructor_thread_id = $this->_thread($this->user);
        $this->_comment($instructor_thread_id, $this->student_user_2);
        $this->_thread($this->student_user_2);
        $this->_thread($this->student_user);

        $response = $this->_discussionsAs($this->student_user)
            ->assertJson(['comments_hidden_until_posted' => false]);
        $this->assertCount(3, $response->json('discussions'));
        $this->assertCount(4, $this->_commentAuthorIds($response));
    }

    /** @test */
    public function a_thread_that_does_not_meet_the_requirements_does_not_unlock_but_stays_visible_to_its_author()
    {
        $this->_thread($this->student_user_2);
        $this->_thread($this->student_user, false);

        $response = $this->_discussionsAs($this->student_user)
            ->assertJson(['comments_hidden_until_posted' => true]);
        $this->assertEquals([$this->student_user->id], $this->_commentAuthorIds($response));
    }

    /** @test */
    public function deleting_the_thread_that_unlocked_the_comments_hides_them_again()
    {
        $this->_thread($this->student_user_2);
        $own_thread_id = $this->_thread($this->student_user);
        $this->_discussionsAs($this->student_user)->assertJson(['comments_hidden_until_posted' => false]);

        DiscussionComment::where('discussion_id', $own_thread_id)->get()->each->delete();
        DB::table('discussions')->where('id', $own_thread_id)->delete();

        $this->_discussionsAs($this->student_user)->assertJson(['comments_hidden_until_posted' => true]);
    }

    /** @test */
    public function instructors_always_see_every_comment()
    {
        $this->_thread($this->student_user_2);
        $this->_thread($this->student_user, false);

        $response = $this->_discussionsAs($this->user)
            ->assertJson(['comments_hidden_until_posted' => false]);
        $this->assertCount(2, $this->_commentAuthorIds($response));
    }

    /** @test */
    public function students_who_can_no_longer_post_see_every_comment()
    {
        //not formative and not assigned to the student, so the student can't post (the same as a closed assignment)
        DB::table('assignments')->where('id', $this->assignment->id)->update(['formative' => 0]);
        $this->_thread($this->student_user_2);

        $response = $this->_discussionsAs($this->student_user)
            ->assertJson(['comments_hidden_until_posted' => false]);
        $this->assertEquals([$this->student_user_2->id], $this->_commentAuthorIds($response));
    }

    /** @test */
    public function students_see_every_comment_when_the_setting_is_off()
    {
        $this->_setSettings(['students_must_post_before_viewing' => '0']);
        $this->_thread($this->student_user_2);

        $response = $this->_discussionsAs($this->student_user)
            ->assertJson(['comments_hidden_until_posted' => false]);
        $this->assertEquals([$this->student_user_2->id], $this->_commentAuthorIds($response));
    }

    /** @test */
    public function before_posting_a_student_can_reply_to_an_instructor_but_not_to_a_classmate()
    {
        $instructor_thread_id = $this->_thread($this->user);
        $classmate_thread_id = $this->_thread($this->student_user_2);

        $this->_post($this->student_user, $classmate_thread_id, ['type' => 'text', 'text' => 'copying your great idea'])
            ->assertJson(['type' => 'error',
                'message' => "You can reply to your classmates once you've started a thread that meets the requirements."]);
        $this->_post($this->student_user, $instructor_thread_id, ['type' => 'text', 'text' => 'answering the instructor here'])
            ->assertJson(['type' => 'success']);
    }

    /** @test */
    public function after_posting_a_student_can_reply_to_a_classmate()
    {
        $classmate_thread_id = $this->_thread($this->student_user_2);
        $this->_post($this->student_user, 0, ['type' => 'text', 'text' => 'my own new thread'])
            ->assertJson(['type' => 'success']);
        $this->_post($this->student_user, $classmate_thread_id, ['type' => 'text', 'text' => 'now I can reply'])
            ->assertJson(['type' => 'success']);
    }

    /*
    |--------------------------------------------------------------------------
    | Attached files
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function a_comment_can_have_attached_files()
    {
        Storage::fake('s3');
        $this->_uploadedFile('abc123.pdf');
        $this->_uploadedFile('def456.xlsx');

        $this->_post($this->student_user, 0, $this->_commentWithFiles(['abc123.pdf' => 'Lab report.pdf', 'def456.xlsx' => 'Data.xlsx']))
            ->assertJson(['type' => 'success']);
        $comment = $this->_newestCommentBy($this->student_user);
        $this->assertDatabaseHas('discussion_comment_attachments', ['discussion_comment_id' => $comment->id, 'file' => 'abc123.pdf', 'original_filename' => 'Lab report.pdf']);
        $this->assertDatabaseHas('discussion_comment_attachments', ['discussion_comment_id' => $comment->id, 'file' => 'def456.xlsx', 'original_filename' => 'Data.xlsx']);
    }

    /** @test */
    public function attached_files_are_shown_with_their_comment()
    {
        Storage::fake('s3');
        $this->_uploadedFile('abc123.pptx');
        $this->_post($this->student_user, 0, $this->_commentWithFiles(['abc123.pptx' => 'Slides.pptx']))
            ->assertJson(['type' => 'success']);

        $response = $this->_discussionsAs($this->user);
        $comment = collect($response->json('discussions'))->pluck('comments')->flatten(1)
            ->firstWhere('created_by_user_id', $this->student_user->id);
        $this->assertCount(1, $comment['attachments']);
        $this->assertEquals('Slides.pptx', $comment['attachments'][0]['original_filename']);
        $this->assertEquals('powerpoint', $comment['attachments'][0]['file_kind']);
    }

    /** @test */
    public function a_file_does_not_make_a_short_comment_meet_the_requirements()
    {
        Storage::fake('s3');
        $this->_uploadedFile('abc123.pdf');
        $this->_post($this->student_user, 0, $this->_commentWithFiles(['abc123.pdf' => 'Lab report.pdf'], 'too short'))
            ->assertJson(['type' => 'success']);
        $this->assertEquals(0, $this->_newestCommentBy($this->student_user)->satisfied_requirement);
        //and so it doesn't unlock classmates' comments either
        $this->_discussionsAs($this->student_user)->assertJson(['comments_hidden_until_posted' => true]);
    }

    /** @test */
    public function a_comment_can_have_at_most_5_files()
    {
        Storage::fake('s3');
        $files = [];
        foreach (range(1, 6) as $number) {
            $this->_uploadedFile("file$number.pdf");
            $files["file$number.pdf"] = "Report $number.pdf";
        }
        $this->_post($this->student_user, 0, $this->_commentWithFiles($files))
            ->assertJsonValidationErrors('attachments');
    }

    /** @test */
    public function files_are_refused_when_attaching_is_off()
    {
        Storage::fake('s3');
        $this->_setSettings(['students_can_attach_files' => '0']);
        $this->_uploadedFile('abc123.pdf');

        $this->_post($this->student_user, 0, $this->_commentWithFiles(['abc123.pdf' => 'Lab report.pdf']))
            ->assertJson(['type' => 'error', 'message' => 'Files cannot be attached to comments for this question.']);
    }

    /** @test */
    public function only_the_file_types_the_instructor_chose_are_accepted()
    {
        Storage::fake('s3');
        $this->_setSettings(['allowed_file_types' => ['pdf']]);
        $this->_uploadedFile('abc123.docx');

        $this->_post($this->student_user, 0, $this->_commentWithFiles(['abc123.docx' => 'Essay.docx']))
            ->assertJson(['type' => 'error', 'message' => 'That type of file is not allowed for this question.']);
    }

    /** @test */
    public function the_uploaded_file_and_its_name_must_be_the_same_allowed_type()
    {
        Storage::fake('s3');
        $this->_uploadedFile('abc123.exe');
        $this->_uploadedFile('def456.pdf');

        $this->_post($this->student_user, 0, $this->_commentWithFiles(['abc123.exe' => 'Lab report.pdf']))
            ->assertJson(['type' => 'error', 'message' => 'That type of file is not allowed for this question.']);
        $this->_post($this->student_user, 0, $this->_commentWithFiles(['def456.pdf' => 'program.exe']))
            ->assertJson(['type' => 'error', 'message' => 'That type of file is not allowed for this question.']);
    }

    /** @test */
    public function files_larger_than_25_mb_are_refused()
    {
        Storage::fake('s3');
        $this->_uploadedFile('abc123.pdf', 26 * 1024);

        $this->_post($this->student_user, 0, $this->_commentWithFiles(['abc123.pdf' => 'Huge.pdf']))
            ->assertJson(['type' => 'error', 'message' => 'A file is too large.  Files can be at most 25 MB.']);
    }

    /** @test */
    public function a_file_that_was_never_uploaded_is_refused()
    {
        Storage::fake('s3');
        $this->_post($this->student_user, 0, $this->_commentWithFiles(['missing.pdf' => 'Lab report.pdf']))
            ->assertJson(['type' => 'error', 'message' => 'We could not find an uploaded file.  Please try uploading it again.']);
    }

    /** @test */
    public function the_original_file_name_is_cleaned()
    {
        Storage::fake('s3');
        $this->_uploadedFile('abc123.pdf');

        $this->_post($this->student_user, 0, $this->_commentWithFiles(['abc123.pdf' => '../../<b>Lab</b> report.pdf']))
            ->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('discussion_comment_attachments', ['file' => 'abc123.pdf', 'original_filename' => 'Lab report.pdf']);
    }

    /** @test */
    public function the_number_of_attached_files_can_be_a_completion_criterion()
    {
        Storage::fake('s3');
        $this->_setSettings(['min_number_of_attached_files' => '2', 'students_must_post_before_viewing' => '0']);
        $this->_uploadedFile('abc123.pdf');
        $this->_uploadedFile('def456.png');
        $discussionComment = new DiscussionComment();

        $this->_post($this->student_user, 0, $this->_commentWithFiles(['abc123.pdf' => 'Lab report.pdf']))
            ->assertJson(['type' => 'success']);
        $result = $discussionComment->satisfiedRequirements($this->assignment, $this->question->id, $this->student_user->id, new \App\AssignmentSyncQuestion());
        $this->assertEquals(1, $result['number_of_attached_files']);
        $this->assertFalse($result['satisfied_min_number_of_attached_files_requirement']);
        $this->assertFalse($result['satisfied_all_requirements']);

        //a reply's files count too
        $this->_post($this->student_user, $this->_thread($this->user), $this->_commentWithFiles(['def456.png' => 'Graph.png']))
            ->assertJson(['type' => 'success']);
        $result = $discussionComment->satisfiedRequirements($this->assignment, $this->question->id, $this->student_user->id, new \App\AssignmentSyncQuestion());
        $this->assertEquals(2, $result['number_of_attached_files']);
        $this->assertTrue($result['satisfied_min_number_of_attached_files_requirement']);
    }

    /** @test */
    public function files_can_be_added_and_removed_when_editing()
    {
        Storage::fake('s3');
        $this->_uploadedFile('abc123.pdf');
        $this->_uploadedFile('def456.docx');
        $this->_uploadedFile('ghi789.png');
        $this->_post($this->student_user, 0, $this->_commentWithFiles(['abc123.pdf' => 'Lab report.pdf', 'def456.docx' => 'Essay.docx']))
            ->assertJson(['type' => 'success']);
        $comment = $this->_newestCommentBy($this->student_user);
        $kept_id = DB::table('discussion_comment_attachments')->where('file', 'abc123.pdf')->value('id');

        $this->_patchAttachments($comment, [$kept_id], ['ghi789.png' => 'Graph.png'])
            ->assertJson(['type' => 'success']);
        $this->assertEquals(['abc123.pdf', 'ghi789.png'], DB::table('discussion_comment_attachments')
            ->where('discussion_comment_id', $comment->id)->orderBy('order')->pluck('file')->toArray());
    }

    /** @test */
    public function editing_cannot_keep_another_comments_files_or_go_over_5()
    {
        Storage::fake('s3');
        foreach (range(1, 6) as $number) {
            $this->_uploadedFile("file$number.pdf");
        }
        $this->_post($this->student_user, 0, $this->_commentWithFiles(['file1.pdf' => 'One.pdf']))->assertJson(['type' => 'success']);
        $first = $this->_newestCommentBy($this->student_user);
        $this->_post($this->student_user, 0, $this->_commentWithFiles(['file2.pdf' => 'Two.pdf']))->assertJson(['type' => 'success']);
        $second = $this->_newestCommentBy($this->student_user);
        $first_attachment_id = DB::table('discussion_comment_attachments')->where('discussion_comment_id', $first->id)->value('id');

        //another comment's file is ignored rather than moved
        $this->_patchAttachments($second, [$first_attachment_id])->assertJson(['type' => 'success']);
        $this->assertDatabaseHas('discussion_comment_attachments', ['id' => $first_attachment_id, 'discussion_comment_id' => $first->id]);

        $own_id = DB::table('discussion_comment_attachments')->where('discussion_comment_id', $first->id)->value('id');
        $this->_patchAttachments($first, [$own_id], ['file3.pdf' => '3.pdf', 'file4.pdf' => '4.pdf', 'file5.pdf' => '5.pdf', 'file6.pdf' => '6.pdf', 'file2.pdf' => '2.pdf'])
            ->assertJson(['type' => 'error', 'message' => 'A comment can have at most 5 files.']);
    }

    /** @test */
    public function deleting_a_comment_deletes_its_attachments()
    {
        Storage::fake('s3');
        $this->_uploadedFile('abc123.pdf');
        $this->_post($this->student_user, 0, $this->_commentWithFiles(['abc123.pdf' => 'Lab report.pdf']))
            ->assertJson(['type' => 'success']);
        $comment = $this->_newestCommentBy($this->student_user);

        $comment->delete();
        $this->assertDatabaseMissing('discussion_comment_attachments', ['discussion_comment_id' => $comment->id]);
    }

    /** @test */
    public function files_can_be_downloaded_under_their_original_name()
    {
        //real temporary links need the s3 disk (a faked disk can't make them), so skip if it isn't configured
        if (!config('filesystems.disks.s3.bucket')) {
            $this->markTestSkipped('No S3 bucket is configured.');
        }
        $urls = DiscussionCommentAttachment::urls('abc123.docx', 'Mi ensayo.docx');
        $this->assertStringContainsString('attachment', urldecode($urls['download_url']));
        $this->assertStringContainsString('Mi ensayo.docx', rawurldecode(urldecode($urls['download_url'])));
        $pdf_urls = DiscussionCommentAttachment::urls('abc123.pdf', 'Lab report.pdf');
        $this->assertStringContainsString('inline', urldecode($pdf_urls['file_url']));
    }
}
