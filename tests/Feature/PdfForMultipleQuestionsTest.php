<?php

namespace Tests\Feature;

use App\Assignment;
use App\AssignToTiming;
use App\CompiledPDFOverride;
use App\Course;
use App\Enrollment;
use App\Question;
use App\Section;
use App\SubmissionFile;
use App\Traits\AssignmentProperties;
use App\Traits\Test;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use function factory;

/**
 * Students upload either a file for one question or one PDF for multiple questions (stored as the type 'a'
 * submission file) and then assign each page to a question.  Pages can only be assigned to questions whose
 * open-ended submission type is 'file'.
 */
class PdfForMultipleQuestionsTest extends TestCase
{
    use Test;

    private $assignment;
    private $student_user;
    private $question;
    private $question_2;

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->student_user->save();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->section = factory(Section::class)->create(['course_id' => $this->course->id]);
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id,
            'solutions_released' => 0,
            'file_upload_mode' => 'both']);
        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id, $this->student_user->id);

        $this->question = factory(Question::class)->create(['page_id' => 1]);
        $this->question_2 = factory(Question::class)->create(['page_id' => 2]);
        foreach ([$this->question, $this->question_2] as $key => $question) {
            DB::table('assignment_question')->insert([
                'assignment_id' => $this->assignment->id,
                'question_id' => $question->id,
                'points' => 10,
                'order' => $key + 1,
                'open_ended_submission_type' => 'file'
            ]);
        }
    }

    /**
     * @param string $original_filename
     * @param string $contents
     * @param string $upload_level
     * @return array
     */
    private function _getSubmissionFileData(string $original_filename, string $contents, string $upload_level): array
    {
        $response = $this->actingAs($this->student_user)->postJson("/api/s3/pre-signed-url", [
            'assignment_id' => $this->assignment->id,
            'upload_file_type' => 'submission',
            'file_name' => $original_filename])->original;
        $s3_key = $response['s3_key'];
        Storage::disk('s3')->put($s3_key, $contents);
        return [
            "submissionFile" => $response['submission'],
            "assignmentId" => $this->assignment->id,
            "questionId" => $this->question->id,
            "type" => "submission",
            "s3_key" => $s3_key,
            "original_filename" => $original_filename,
            "uploadLevel" => $upload_level,
            "_method" => "put"
        ];
    }

    /**
     * @param int $number_of_pages
     * @return string a real PDF so that its pages can be counted
     */
    private function _pdfContents(int $number_of_pages = 3): string
    {
        $pdf = new \FPDF();
        for ($i = 0; $i < $number_of_pages; $i++) {
            $pdf->AddPage();
        }
        return $pdf->Output('S');
    }

    private function _createCompiledPdf(): SubmissionFile
    {
        return factory(SubmissionFile::class)->create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'type' => 'a',
            'question_id' => null,
            'submission' => 'compiled.pdf',
            'original_filename' => 'my work.pdf',
            'number_of_pages' => 3]);
    }

    private function _setOpenEndedSubmissionType(int $question_id, string $open_ended_submission_type)
    {
        DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $question_id)
            ->update(['open_ended_submission_type' => $open_ended_submission_type]);
    }

    private function _makePastDue()
    {
        $assignToTiming = AssignToTiming::where('assignment_id', $this->assignment->id)->first();
        $assignToTiming->due = Carbon::yesterday();
        $assignToTiming->save();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function student_can_upload_a_pdf_for_multiple_questions()
    {
        $submission_file_data = $this->_getSubmissionFileData('my work.pdf', $this->_pdfContents(), 'assignment');
        $response = $this->actingAs($this->student_user)->postJson("/api/submission-files", $submission_file_data)
            ->assertJson(['type' => 'success',
                'message' => 'Your PDF has been uploaded.  Now choose which page goes with each question.']);
        $this->assertNotEmpty($response['full_pdf_url']);

        $compiled_pdf = SubmissionFile::where('assignment_id', $this->assignment->id)
            ->where('user_id', $this->student_user->id)
            ->where('type', 'a')
            ->first();
        $this->assertNotNull($compiled_pdf);
        $this->assertNull($compiled_pdf->question_id);
        $this->assertEquals(1, $compiled_pdf->upload_count);
        //nothing is assigned to a question until the student assigns pages
        $this->assertEquals(0, SubmissionFile::where('assignment_id', $this->assignment->id)
            ->where('user_id', $this->student_user->id)
            ->where('type', 'q')
            ->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function uploading_a_pdf_for_multiple_questions_saves_its_number_of_pages()
    {
        $submission_file_data = $this->_getSubmissionFileData('my work.pdf', $this->_pdfContents(2), 'assignment');
        $this->actingAs($this->student_user)->postJson("/api/submission-files", $submission_file_data)
            ->assertJson(['type' => 'success', 'number_of_pages' => 2]);
        $this->assertEquals(2, SubmissionFile::where('assignment_id', $this->assignment->id)
            ->where('user_id', $this->student_user->id)
            ->where('type', 'a')
            ->first()
            ->number_of_pages);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_pdf_whose_pages_cannot_be_counted_can_still_be_uploaded()
    {
        $submission_file_data = $this->_getSubmissionFileData('my work.pdf', "%PDF-1.4\nnot really a pdf\n%%EOF\n", 'assignment');
        $this->actingAs($this->student_user)->postJson("/api/submission-files", $submission_file_data)
            ->assertJson(['type' => 'success', 'number_of_pages' => null]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_assign_a_page_past_the_end_of_the_pdf()
    {
        $this->_createCompiledPdf();
        $this->actingAs($this->student_user)->patchJson("/api/submission-files/{$this->assignment->id}/{$this->question->id}/page",
            ['page' => 4, 'question_number' => 1])
            ->assertJson(['type' => 'error',
                'message' => 'Your PDF only has 3 pages.  Please choose a page from 1 to 3.',
                'number_of_pages' => 3]);
        $this->assertFalse(SubmissionFile::where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->exists());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_pages_of_a_pdf_uploaded_before_they_were_counted_are_counted_when_assigning_a_page()
    {
        $compiled_pdf = $this->_createCompiledPdf();
        $compiled_pdf->number_of_pages = null;
        $compiled_pdf->save();
        Storage::disk('s3')->put("assignments/{$this->assignment->id}/compiled.pdf", $this->_pdfContents(2));

        $this->actingAs($this->student_user)->patchJson("/api/submission-files/{$this->assignment->id}/{$this->question->id}/page",
            ['page' => 3, 'question_number' => 1])
            ->assertJson(['type' => 'error',
                'message' => 'Your PDF only has 2 pages.  Please choose a page from 1 to 2.']);
        $this->assertEquals(2, $compiled_pdf->fresh()->number_of_pages);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_file_for_multiple_questions_must_be_a_pdf()
    {
        $submission_file_data = $this->_getSubmissionFileData('my work.txt', 'some file contents', 'assignment');
        $response = $this->actingAs($this->student_user)->postJson("/api/submission-files", $submission_file_data)
            ->assertJson(['type' => 'error']);
        $this->assertStringContainsString('Only a PDF can be used for multiple questions.', $response['message']);
        $this->assertFalse(SubmissionFile::where('assignment_id', $this->assignment->id)
            ->where('type', 'a')
            ->exists());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_non_pdf_file_can_still_be_uploaded_for_a_single_question()
    {
        $submission_file_data = $this->_getSubmissionFileData('my work.txt', 'some file contents', 'question');
        $this->actingAs($this->student_user)->postJson("/api/submission-files", $submission_file_data)
            ->assertJson(['type' => 'success', 'message' => 'Your file submission has been saved.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function upload_level_must_be_valid()
    {
        $submission_file_data = $this->_getSubmissionFileData('my work.pdf', $this->_pdfContents(), 'bogus');
        $this->actingAs($this->student_user)->postJson("/api/submission-files", $submission_file_data)
            ->assertJson(['type' => 'error', 'message' => 'bogus is not a valid upload level.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function single_question_uploads_do_not_count_against_the_pdf_for_multiple_questions()
    {
        //the student has used up all of their uploads for this one question...
        factory(SubmissionFile::class)->create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'type' => 'q',
            'upload_count' => 15]);
        //...but can still upload a PDF for multiple questions from that question's page
        $submission_file_data = $this->_getSubmissionFileData('my work.pdf', $this->_pdfContents(), 'assignment');
        $this->actingAs($this->student_user)->postJson("/api/submission-files", $submission_file_data)
            ->assertJson(['type' => 'success']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_upload_a_pdf_for_multiple_questions_more_than_the_max_number_of_times()
    {
        $compiled_pdf = $this->_createCompiledPdf();
        $compiled_pdf->upload_count = 15;
        $compiled_pdf->save();
        $submission_file_data = $this->_getSubmissionFileData('my work.pdf', $this->_pdfContents(), 'assignment');
        $this->actingAs($this->student_user)->postJson("/api/submission-files", $submission_file_data)
            ->assertJson(['type' => 'error',
                'message' => 'You have exceeded the number of times that you can re-upload a file submission.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_upload_a_pdf_for_multiple_questions_if_past_due()
    {
        $this->_makePastDue();
        $submission_file_data = $this->_getSubmissionFileData('my work.pdf', $this->_pdfContents(), 'assignment');
        $this->actingAs($this->student_user)->postJson("/api/submission-files", $submission_file_data)
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since the due date for this assignment has passed.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_upload_a_pdf_for_multiple_questions_if_past_due_with_a_pdf_upload_override()
    {
        $this->_makePastDue();
        CompiledPDFOverride::create(['assignment_id' => $this->assignment->id,
            'user_id' => $this->student_user->id,
            'set_page_only' => '0'
        ]);
        $submission_file_data = $this->_getSubmissionFileData('my work.pdf', $this->_pdfContents(), 'assignment');
        $this->actingAs($this->student_user)->postJson("/api/submission-files", $submission_file_data)
            ->assertJson(['type' => 'success']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function student_can_assign_pages_from_the_pdf_to_multiple_questions()
    {
        $this->_createCompiledPdf();
        $this->actingAs($this->student_user)->patchJson("/api/submission-files/{$this->assignment->id}/{$this->question->id}/page",
            ['page' => 1, 'question_number' => 1])
            ->assertJson(['type' => 'success', 'page' => 1, 'submission' => 'compiled.pdf']);
        $this->actingAs($this->student_user)->patchJson("/api/submission-files/{$this->assignment->id}/{$this->question_2->id}/page",
            ['page' => 3, 'question_number' => 2])
            ->assertJson(['type' => 'success', 'page' => 3]);

        foreach ([$this->question->id => 1, $this->question_2->id => 3] as $question_id => $page) {
            $submission_file = SubmissionFile::where('assignment_id', $this->assignment->id)
                ->where('user_id', $this->student_user->id)
                ->where('question_id', $question_id)
                ->where('type', 'q')
                ->first();
            $this->assertEquals('compiled.pdf', $submission_file->submission);
            $this->assertEquals($page, $submission_file->page);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function assigning_a_page_replaces_a_file_uploaded_for_just_that_question()
    {
        $this->_createCompiledPdf();
        factory(SubmissionFile::class)->create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'type' => 'q',
            'submission' => 'just-this-question.png',
            'page' => null]);
        $this->actingAs($this->student_user)->patchJson("/api/submission-files/{$this->assignment->id}/{$this->question->id}/page",
            ['page' => 2, 'question_number' => 1])
            ->assertJson(['type' => 'success']);

        $submission_files = SubmissionFile::where('assignment_id', $this->assignment->id)
            ->where('user_id', $this->student_user->id)
            ->where('question_id', $this->question->id)
            ->get();
        $this->assertCount(1, $submission_files);
        $this->assertEquals('compiled.pdf', $submission_files[0]->submission);
        $this->assertEquals(2, $submission_files[0]->page);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_assign_a_page_before_uploading_a_pdf()
    {
        $this->actingAs($this->student_user)->patchJson("/api/submission-files/{$this->assignment->id}/{$this->question->id}/page",
            ['page' => 1, 'question_number' => 1])
            ->assertJson(['type' => 'error', 'message' => 'Please upload your PDF before assigning pages.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_assign_a_page_to_a_rich_text_question()
    {
        $this->_createCompiledPdf();
        $this->_setOpenEndedSubmissionType($this->question->id, 'text');
        $this->actingAs($this->student_user)->patchJson("/api/submission-files/{$this->assignment->id}/{$this->question->id}/page",
            ['page' => 1, 'question_number' => 1])
            ->assertJson(['type' => 'error',
                'message' => 'Pages from your PDF can only be assigned to questions that accept a file upload.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_assign_a_page_to_an_audio_question()
    {
        $this->_createCompiledPdf();
        $this->_setOpenEndedSubmissionType($this->question->id, 'audio');
        $this->actingAs($this->student_user)->patchJson("/api/submission-files/{$this->assignment->id}/{$this->question->id}/page",
            ['page' => 1, 'question_number' => 1])
            ->assertJson(['type' => 'error',
                'message' => 'Pages from your PDF can only be assigned to questions that accept a file upload.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_assign_a_page_to_a_question_without_an_open_ended_submission()
    {
        $this->_createCompiledPdf();
        $this->_setOpenEndedSubmissionType($this->question->id, '0');
        $this->actingAs($this->student_user)->patchJson("/api/submission-files/{$this->assignment->id}/{$this->question->id}/page",
            ['page' => 1, 'question_number' => 1])
            ->assertJson(['type' => 'error',
                'message' => 'Pages from your PDF can only be assigned to questions that accept a file upload.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function delayed_assignments_always_use_the_both_file_upload_mode()
    {
        $properties = new class {
            use AssignmentProperties;
        };
        $this->assertEquals('both', $properties->getFileUploadMode((object)['assessment_type' => 'delayed']));
        $this->assertNull($properties->getFileUploadMode((object)['assessment_type' => 'real time']));
    }

    /**
     * @param Assignment $assignment
     * @return string|null
     */
    private function _fileUploadMode(Assignment $assignment)
    {
        return DB::table('assignments')->where('id', $assignment->id)->value('file_upload_mode');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function convert_saves_the_original_file_upload_modes_and_switches_them_to_both()
    {
        $compiled_pdf = factory(Assignment::class)->create(['course_id' => $this->course->id, 'file_upload_mode' => 'compiled_pdf']);
        $individual = factory(Assignment::class)->create(['course_id' => $this->course->id, 'file_upload_mode' => 'individual_assessment']);

        $this->artisan('assignments:convert-file-upload-modes')->assertExitCode(0);

        $this->assertEquals('both', $this->_fileUploadMode($compiled_pdf));
        $this->assertEquals('both', $this->_fileUploadMode($individual));
        $this->assertEquals('compiled_pdf', DB::table('file_upload_mode_backups')->where('assignment_id', $compiled_pdf->id)->value('file_upload_mode'));
        $this->assertEquals('individual_assessment', DB::table('file_upload_mode_backups')->where('assignment_id', $individual->id)->value('file_upload_mode'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function restore_puts_back_the_original_file_upload_modes()
    {
        $compiled_pdf = factory(Assignment::class)->create(['course_id' => $this->course->id, 'file_upload_mode' => 'compiled_pdf']);
        $individual = factory(Assignment::class)->create(['course_id' => $this->course->id, 'file_upload_mode' => 'individual_assessment']);
        $this->artisan('assignments:convert-file-upload-modes')->assertExitCode(0);

        $this->artisan('assignments:convert-file-upload-modes', ['--restore' => true])->assertExitCode(0);

        $this->assertEquals('compiled_pdf', $this->_fileUploadMode($compiled_pdf));
        $this->assertEquals('individual_assessment', $this->_fileUploadMode($individual));
        //assignments that weren't backed up are left alone
        $this->assertEquals('both', $this->_fileUploadMode($this->assignment));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function convert_can_be_redone_after_a_restore()
    {
        $assignment = factory(Assignment::class)->create(['course_id' => $this->course->id, 'file_upload_mode' => 'compiled_pdf']);
        $this->artisan('assignments:convert-file-upload-modes')->assertExitCode(0);
        $this->artisan('assignments:convert-file-upload-modes', ['--restore' => true])->assertExitCode(0);
        //the instructor changes the mode while the old code is back
        DB::table('assignments')->where('id', $assignment->id)->update(['file_upload_mode' => 'individual_assessment']);

        $this->artisan('assignments:convert-file-upload-modes')->assertExitCode(0);

        $this->assertEquals('both', $this->_fileUploadMode($assignment));
        $this->assertEquals('individual_assessment', DB::table('file_upload_mode_backups')->where('assignment_id', $assignment->id)->value('file_upload_mode'));
        $this->artisan('assignments:convert-file-upload-modes', ['--restore' => true])->assertExitCode(0);
        $this->assertEquals('individual_assessment', $this->_fileUploadMode($assignment));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function running_convert_twice_keeps_the_original_file_upload_modes()
    {
        $assignment = factory(Assignment::class)->create(['course_id' => $this->course->id, 'file_upload_mode' => 'compiled_pdf']);
        $this->artisan('assignments:convert-file-upload-modes')->assertExitCode(0);
        $this->artisan('assignments:convert-file-upload-modes')->assertExitCode(0);

        $this->assertEquals('compiled_pdf', DB::table('file_upload_mode_backups')->where('assignment_id', $assignment->id)->value('file_upload_mode'));
    }
}
