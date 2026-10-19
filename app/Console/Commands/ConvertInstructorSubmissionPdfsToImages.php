<?php

namespace App\Console\Commands;

use App\Jobs\ConvertSubmissionPdfToImages;
use App\SubmissionFileConversion;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time backfill: page images for every student PDF and image submission in the courses owned by one instructor
 * (PDFs are rendered page by page; an image becomes a single page as-is).
 *
 * php artisan submission-files:convert-pdfs-to-images 123 --dry-run
 * php artisan submission-files:convert-pdfs-to-images 123
 * php artisan submission-files:convert-pdfs-to-images 123 --student=456 --student=789
 *
 * On Vapor:  vapor command production --command="submission-files:convert-pdfs-to-images 123 --dry-run"
 */
class ConvertInstructorSubmissionPdfsToImages extends Command
{
    /**
     * @var string
     */
    protected $signature = 'submission-files:convert-pdfs-to-images
                            {instructor_id : The user id of the instructor whose courses to backfill}
                            {--course= : Only this course id (must belong to the instructor)}
                            {--student=* : Only submissions from this student id (repeat for more than one)}
                            {--dry-run : List what would be converted without queueing anything}
                            {--force : Re-render PDFs that already have completed images}
                            {--sync : Convert in this process instead of queueing (only for a handful of files; Vapor CLI times out at 600s)}';

    /**
     * @var string
     */
    protected $description = "Renders page images for student PDF submissions in an instructor's courses";

    /**
     * @return int
     */
    public function handle(): int
    {
        $instructor = User::find($this->argument('instructor_id'));
        if (!$instructor) {
            $this->error('No user with id ' . $this->argument('instructor_id') . '.');
            return 1;
        }
        if ((int)$instructor->role !== 2) {
            $this->error("$instructor->first_name $instructor->last_name (id $instructor->id) is not an instructor.");
            return 1;
        }

        $courses = DB::table('courses')->where('user_id', $instructor->id);
        if ($this->option('course')) {
            $courses->where('id', $this->option('course'));
        }
        $course_ids = $courses->pluck('id');
        $student_ids = array_values(array_unique(array_map('intval', $this->option('student'))));
        foreach ($student_ids as $student_id) {
            if ($student_id <= 0) {
                $this->error('Each --student must be a user id.');
                return 1;
            }
        }
        if ($course_ids->isEmpty()) {
            $this->warn("No matching courses for $instructor->first_name $instructor->last_name.");
            return 0;
        }

        // Distinct uploaded PDFs. A compiled PDF appears once as type 'a' and again on every type 'q' row
        // assigned a page from it; the job is keyed by (assignment, file), so each file is rendered once.
        $files = DB::table('submission_files')
            ->join('assignments', 'submission_files.assignment_id', '=', 'assignments.id')
            ->whereIn('assignments.course_id', $course_ids)
            ->whereIn('submission_files.type', ['a', 'q'])
            ->when($student_ids, function ($query) use ($student_ids) {
                $query->whereIn('submission_files.user_id', $student_ids);
            })
            ->where(function ($query) {
                foreach (['pdf', 'jpg', 'jpeg', 'png', 'gif'] as $extension) {
                    $query->orWhereRaw("LOWER(submission_files.submission) LIKE ?", ["%.$extension"]);
                }
            })
            ->select('submission_files.assignment_id', 'submission_files.submission')
            ->distinct()
            ->orderBy('submission_files.assignment_id')
            ->get();

        $completed = [];
        if (!$this->option('force')) {
            SubmissionFileConversion::whereIn('assignment_id', $files->pluck('assignment_id')->unique())
                ->where('status', SubmissionFileConversion::COMPLETED)
                ->get(['assignment_id', 'submission'])
                ->each(function ($conversion) use (&$completed) {
                    $completed["$conversion->assignment_id/$conversion->submission"] = true;
                });
        }
        $to_convert = $files->reject(function ($file) use ($completed) {
            return isset($completed["$file->assignment_id/$file->submission"]);
        })->values();

        $this->info("$instructor->first_name $instructor->last_name (id $instructor->id): "
            . $course_ids->count() . ' course(s), '
            . ($student_ids ? count($student_ids) . ' student(s), ' : '')
            . $files->count() . ' file(s) found, '
            . (count($files) - count($to_convert)) . ' already converted, '
            . $to_convert->count() . ' to convert.');

        if ($this->option('dry-run')) {
            $by_assignment = $to_convert->groupBy('assignment_id');
            $rows = [];
            foreach ($by_assignment as $assignment_id => $assignment_files) {
                $rows[] = [$assignment_id, $assignment_files->count()];
            }
            if ($rows) {
                $this->table(['Assignment id', 'Files to convert'], $rows);
            }
            $this->line('Dry run: nothing was queued.');
            return 0;
        }

        $bar = $this->output->createProgressBar($to_convert->count());
        foreach ($to_convert as $file) {
            $job = new ConvertSubmissionPdfToImages((int)$file->assignment_id, $file->submission, (bool)$this->option('force'));
            $this->option('sync') ? dispatch_now($job) : dispatch($job);
            $bar->advance();
        }
        $bar->finish();
        $this->line(''); // newLine() is Laravel 8+

        $this->info($this->option('sync')
            ? 'Done. Check submission_file_conversions for any with status "failed".'
            : 'Queued. Progress is in submission_file_conversions (status pending → processing → completed/failed).');
        return 0;
    }
}
