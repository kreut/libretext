<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Jobs\ConvertSubmissionPdfToImages;
use App\SubmissionFileConversion;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The backfill command. No PDF tooling needed: jobs are faked.
 */
class ConvertInstructorSubmissionPdfsToImagesTest extends TestCase
{
    private $instructor;
    private $other_instructor;
    private $student;
    private $course;
    private $assignment;
    private $other_assignment;

    public function setup(): void
    {
        parent::setUp();
        $this->instructor = factory(User::class)->create();
        $this->other_instructor = factory(User::class)->create();
        $this->student = factory(User::class)->create(['role' => 3]);

        $this->course = factory(Course::class)->create(['user_id' => $this->instructor->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

        $other_course = factory(Course::class)->create(['user_id' => $this->other_instructor->id]);
        $this->other_assignment = factory(Assignment::class)->create(['course_id' => $other_course->id]);

        // A compiled PDF (type 'a') with two questions pointing at it (type 'q'): one file, not three.
        $this->_submissionFile($this->assignment->id, 'a', null, 'compiled.pdf');
        $this->_submissionFile($this->assignment->id, 'q', 1, 'compiled.pdf', 1);
        $this->_submissionFile($this->assignment->id, 'q', 2, 'compiled.pdf', 3);
        // A PDF uploaded for one question only, with an upper-case extension.
        $this->_submissionFile($this->assignment->id, 'q', 3, 'single.PDF');
        // A photo: becomes a single page.
        $this->_submissionFile($this->assignment->id, 'q', 4, 'photo.jpg');
        // Text, audio: ignored.
        $this->_submissionFile($this->assignment->id, 'q', 5, 'text.html');
        $this->_submissionFile($this->assignment->id, 'q', 6, 'recording.mp3');
        // Another instructor's PDF: ignored.
        $this->_submissionFile($this->other_assignment->id, 'q', 1, 'not-mine.pdf');
    }

    /** @test */
    public function it_queues_one_job_per_distinct_pdf_or_image_in_the_instructors_courses()
    {
        Queue::fake();
        $this->artisan("submission-files:convert-pdfs-to-images {$this->instructor->id}")
            ->assertExitCode(0)
            ->run();
        Queue::assertPushed(ConvertSubmissionPdfToImages::class, 3);
    }

    /** @test */
    public function dry_run_reports_counts_and_queues_nothing()
    {
        Queue::fake();
        $name = "{$this->instructor->first_name} {$this->instructor->last_name}";
        $this->artisan("submission-files:convert-pdfs-to-images {$this->instructor->id} --dry-run")
            ->expectsOutput("$name (id {$this->instructor->id}): 1 course(s), 3 file(s) found, 0 already converted, 3 to convert.")
            ->expectsOutput('Dry run: nothing was queued.')
            ->assertExitCode(0)
            ->run();
        Queue::assertNothingPushed();
    }

    /** @test */
    public function it_skips_pdfs_that_are_already_converted()
    {
        Queue::fake();
        SubmissionFileConversion::create([
            'assignment_id' => $this->assignment->id,
            'submission' => 'compiled.pdf',
            'status' => SubmissionFileConversion::COMPLETED,
            'number_of_pages' => 3
        ]);
        $this->artisan("submission-files:convert-pdfs-to-images {$this->instructor->id}")
            ->assertExitCode(0)
            ->run();
        Queue::assertPushed(ConvertSubmissionPdfToImages::class, 2);
    }

    /** @test */
    public function force_requeues_pdfs_that_are_already_converted()
    {
        Queue::fake();
        SubmissionFileConversion::create([
            'assignment_id' => $this->assignment->id,
            'submission' => 'compiled.pdf',
            'status' => SubmissionFileConversion::COMPLETED,
            'number_of_pages' => 3
        ]);
        $this->artisan("submission-files:convert-pdfs-to-images {$this->instructor->id} --force")
            ->assertExitCode(0)
            ->run();
        Queue::assertPushed(ConvertSubmissionPdfToImages::class, 3);
    }

    /** @test */
    public function failed_conversions_are_retried()
    {
        Queue::fake();
        SubmissionFileConversion::create([
            'assignment_id' => $this->assignment->id,
            'submission' => 'compiled.pdf',
            'status' => SubmissionFileConversion::FAILED,
            'error_message' => 'pdftoppm failed'
        ]);
        $this->artisan("submission-files:convert-pdfs-to-images {$this->instructor->id}")
            ->assertExitCode(0)
            ->run();
        Queue::assertPushed(ConvertSubmissionPdfToImages::class, 3);
    }

    /** @test */
    public function course_option_limits_to_that_course()
    {
        Queue::fake();
        $second_course = factory(Course::class)->create(['user_id' => $this->instructor->id]);
        $second_assignment = factory(Assignment::class)->create(['course_id' => $second_course->id]);
        $this->_submissionFile($second_assignment->id, 'q', 1, 'second-course.pdf');

        $this->artisan("submission-files:convert-pdfs-to-images {$this->instructor->id} --course=$second_course->id")
            ->assertExitCode(0)
            ->run();
        Queue::assertPushed(ConvertSubmissionPdfToImages::class, 1);
    }

    /** @test */
    public function student_option_limits_to_those_students()
    {
        Queue::fake();
        $other_student = factory(User::class)->create(['role' => 3]);
        DB::table('submission_files')->insert([
            'type' => 'q',
            'user_id' => $other_student->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => 1,
            'original_filename' => 'other.pdf',
            'submission' => 'other.pdf',
            'date_submitted' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->artisan("submission-files:convert-pdfs-to-images {$this->instructor->id} --student={$other_student->id}")
            ->assertExitCode(0)
            ->run();
        Queue::assertPushed(ConvertSubmissionPdfToImages::class, 1);
        Queue::assertPushed(ConvertSubmissionPdfToImages::class, function ($job) {
            return $this->_jobSubmission($job) === 'other.pdf';
        });
    }

    /** @test */
    public function student_option_can_be_repeated()
    {
        Queue::fake();
        $other_student = factory(User::class)->create(['role' => 3]);
        DB::table('submission_files')->insert([
            'type' => 'q',
            'user_id' => $other_student->id,
            'assignment_id' => $this->assignment->id,
            'question_id' => 1,
            'original_filename' => 'other.pdf',
            'submission' => 'other.pdf',
            'date_submitted' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->artisan("submission-files:convert-pdfs-to-images {$this->instructor->id} --student={$this->student->id} --student={$other_student->id}")
            ->assertExitCode(0)
            ->run();
        Queue::assertPushed(ConvertSubmissionPdfToImages::class, 4);
    }

    /** @test */
    public function a_student_outside_the_instructors_courses_finds_nothing()
    {
        Queue::fake();
        $outsider = factory(User::class)->create(['role' => 3]);
        $this->artisan("submission-files:convert-pdfs-to-images {$this->instructor->id} --student={$outsider->id}")
            ->assertExitCode(0)
            ->run();
        Queue::assertNothingPushed();
    }

    /**
     * @param ConvertSubmissionPdfToImages $job
     * @return string
     */
    private function _jobSubmission(ConvertSubmissionPdfToImages $job): string
    {
        $property = new \ReflectionProperty($job, 'submission');
        $property->setAccessible(true);
        return $property->getValue($job);
    }

    /** @test */
    public function it_refuses_a_user_who_is_not_an_instructor()
    {
        Queue::fake();
        $this->artisan("submission-files:convert-pdfs-to-images {$this->student->id}")
            ->expectsOutput("{$this->student->first_name} {$this->student->last_name} (id {$this->student->id}) is not an instructor.")
            ->assertExitCode(1)
            ->run();
        Queue::assertNothingPushed();
    }

    /** @test */
    public function it_refuses_a_user_that_does_not_exist()
    {
        Queue::fake();
        $this->artisan('submission-files:convert-pdfs-to-images 999999999')
            ->expectsOutput('No user with id 999999999.')
            ->assertExitCode(1)
            ->run();
        Queue::assertNothingPushed();
    }

    /**
     * @param int $assignment_id
     * @param string $type
     * @param int|null $question_id
     * @param string $submission
     * @param int|null $page
     * @return void
     */
    private function _submissionFile(int $assignment_id, string $type, ?int $question_id, string $submission, ?int $page = null): void
    {
        DB::table('submission_files')->insert([
            'type' => $type,
            'user_id' => $this->student->id,
            'assignment_id' => $assignment_id,
            'question_id' => $question_id,
            'original_filename' => $submission,
            'submission' => $submission,
            'page' => $page,
            'date_submitted' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
