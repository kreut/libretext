<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Jobs\ConvertSubmissionPdfToImages;
use App\Question;
use App\Section;
use App\SubmissionFileConversion;
use App\SubmissionFilePage;
use App\SubmissionPageAnnotation;
use App\Traits\Test;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

/**
 * End pages, markup being cleared when the student changes their submission, conversions being queued on
 * upload, and page images reaching graders but not students.
 */
class SubmissionPagesFlowTest extends TestCase
{
    use Test;

    private $instructor;
    private $student;
    private $course;
    private $assignment;
    private $question;
    private $question_2;

    public function setup(): void
    {
        parent::setUp();
        $this->instructor = factory(User::class)->create();
        $this->student = factory(User::class)->create(['role' => 3]);
        $this->course = factory(Course::class)->create(['user_id' => $this->instructor->id]);
        $section = factory(Section::class)->create(['course_id' => $this->course->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id, 'show_scores' => 0]);
        $this->question = factory(Question::class)->create();
        $this->question_2 = factory(Question::class)->create();
        foreach ([$this->question, $this->question_2] as $key => $question) {
            DB::table('assignment_question')->insert([
                'assignment_id' => $this->assignment->id,
                'question_id' => $question->id,
                'points' => 10,
                'order' => $key + 1,
                'open_ended_submission_type' => 'file'
            ]);
        }
        Enrollment::create(['course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'section_id' => $section->id]);
        $this->assignUserToAssignment($this->assignment->id, 'course', $this->course->id, $this->student->id);

        // the student's compiled PDF: 6 pages
        DB::table('submission_files')->insert([
            'type' => 'a',
            'user_id' => $this->student->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => null,
            'original_filename' => 'homework.pdf',
            'submission' => 'compiled.pdf',
            'number_of_pages' => 6,
            'date_submitted' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * A fake S3 that can also hand out temporary URLs (the plain fake disk can't).
     *
     * @return void
     */
    private function _fakeS3(): void
    {
        Storage::fake('local');
        $fake = Storage::fake('s3');
        $disk = Mockery::mock($fake);
        $disk->shouldReceive('temporaryUrl')->andReturnUsing(function ($key) {
            return "https://s3.test/$key";
        });
        Storage::set('s3', $disk);
    }

    private function _pageUrl(Question $question): string
    {
        return "/api/submission-files/{$this->assignment->id}/{$question->id}/page";
    }

    private function _annotate(Question $question, int $page): void
    {
        SubmissionPageAnnotation::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'user_id' => $this->student->id,
            'page' => $page,
            'state' => ['width' => 1275, 'height' => 1650, 'markers' => [['typeName' => 'ArrowMarker']]],
            'grader_id' => $this->instructor->id
        ]);
    }

    private function _pdf(int $number_of_pages): string
    {
        $pdf = new Fpdi('P', 'pt', 'Letter');
        $pdf->SetFont('Helvetica', '', 24);
        for ($i = 1; $i <= $number_of_pages; $i++) {
            $pdf->AddPage();
            $pdf->Text(72, 72, "Page $i");
        }
        return $pdf->Output('S');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function assigning_a_page_range_saves_the_end_page()
    {
        $this->actingAs($this->student)
            ->patchJson($this->_pageUrl($this->question), ['page' => 2, 'end_page' => 4, 'question_number' => 1])
            ->assertJson(['type' => 'success', 'page' => 2, 'end_page' => 4]);

        $this->assertDatabaseHas('submission_files', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student->id,
            'type' => 'q',
            'submission' => 'compiled.pdf',
            'page' => 2,
            'end_page' => 4
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function an_end_page_past_the_end_of_the_pdf_is_rejected()
    {
        $this->actingAs($this->student)
            ->patchJson($this->_pageUrl($this->question), ['page' => 2, 'end_page' => 9, 'question_number' => 1])
            ->assertJson(['type' => 'error',
                'message' => 'Your PDF only has 6 pages.  Please choose a page from 1 to 6.']);

        $this->assertDatabaseMissing('submission_files', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'user_id' => $this->student->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reassigning_pages_deletes_that_questions_markup_only()
    {
        $this->_annotate($this->question, 2);
        $this->_annotate($this->question_2, 5);

        $this->actingAs($this->student)
            ->patchJson($this->_pageUrl($this->question), ['page' => 3, 'end_page' => 4, 'question_number' => 1])
            ->assertJson(['type' => 'success']);

        $this->assertDatabaseMissing('submission_page_annotations', ['question_id' => $this->question->id]);
        $this->assertDatabaseHas('submission_page_annotations', ['question_id' => $this->question_2->id, 'page' => 5]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function uploading_a_new_file_for_a_question_clears_its_pages_and_markup_and_queues_a_conversion()
    {
        $this->_fakeS3();
        Queue::fake();
        // the question currently points at pages 2-4 of the compiled PDF, and has markup
        DB::table('submission_files')->insert([
            'type' => 'q',
            'user_id' => $this->student->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'original_filename' => 'homework.pdf',
            'submission' => 'compiled.pdf',
            'page' => 2,
            'end_page' => 4,
            'date_submitted' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->_annotate($this->question, 3);
        $s3_key = "assignments/{$this->assignment->id}/new-upload.pdf";
        Storage::disk('s3')->put($s3_key, $this->_pdf(2));

        $this->actingAs($this->student)
            ->putJson('/api/submission-files', [
                's3_key' => $s3_key,
                'assignmentId' => $this->assignment->id,
                'questionId' => $this->question->id,
                'uploadLevel' => 'question',
                'original_filename' => 'answer.pdf'
            ])
            ->assertJson(['type' => 'success']);

        $this->assertDatabaseHas('submission_files', [
            'question_id' => $this->question->id,
            'user_id' => $this->student->id,
            'submission' => 'new-upload.pdf',
            'page' => null,
            'end_page' => null
        ]);
        $this->assertDatabaseMissing('submission_page_annotations', ['question_id' => $this->question->id]);
        Queue::assertPushed(ConvertSubmissionPdfToImages::class, 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function uploading_a_text_file_does_not_queue_a_conversion()
    {
        $this->_fakeS3();
        Queue::fake();
        $s3_key = "assignments/{$this->assignment->id}/notes.txt";
        Storage::disk('s3')->put($s3_key, 'my answer is 42');

        $this->actingAs($this->student)
            ->putJson('/api/submission-files', [
                's3_key' => $s3_key,
                'assignmentId' => $this->assignment->id,
                'questionId' => $this->question->id,
                'uploadLevel' => 'question',
                'original_filename' => 'notes.txt'
            ])
            ->assertJson(['type' => 'success']);

        Queue::assertNotPushed(ConvertSubmissionPdfToImages::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_grader_gets_the_questions_page_images_and_the_student_does_not()
    {
        $this->_fakeS3();
        DB::table('submission_files')->insert([
            'type' => 'q',
            'user_id' => $this->student->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'original_filename' => 'homework.pdf',
            'submission' => 'compiled.pdf',
            'page' => 2,
            'end_page' => 4,
            'date_submitted' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        SubmissionFileConversion::create([
            'assignment_id' => $this->assignment->id,
            'submission' => 'compiled.pdf',
            'status' => SubmissionFileConversion::COMPLETED,
            'number_of_pages' => 6
        ]);
        for ($page = 1; $page <= 6; $page++) {
            SubmissionFilePage::create([
                'assignment_id' => $this->assignment->id,
                'submission' => 'compiled.pdf',
                'page' => $page,
                's3_key' => SubmissionFilePage::s3Key($this->assignment->id, 'compiled.pdf', $page),
                'width' => 1275,
                'height' => 1650
            ]);
        }
        $url = "/api/submission-files/get-files-from-s3/{$this->assignment->id}/{$this->question->id}/{$this->student->id}";

        $response = $this->actingAs($this->instructor)
            ->postJson($url, ['open_ended_submission_type' => 'file'])
            ->assertJson(['type' => 'success', 'files' => ['page_images_status' => 'completed']]);
        $this->assertEquals([2, 3, 4], array_column($response->json('files.page_images'), 'page'));

        $student_response = $this->actingAs($this->student)
            ->postJson($url, ['open_ended_submission_type' => 'file']);
        $this->assertEmpty($student_response->json('files.page_images'));
    }
}
