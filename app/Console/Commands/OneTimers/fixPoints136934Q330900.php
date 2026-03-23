<?php

namespace App\Console\Commands\OneTimers;

use App\Score;
use App\SubmissionFile;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class fixPoints136934Q330900 extends Command
{
    protected $signature = 'fix:Points136934Q330900';

    protected $description = 'DRY RUN: Preview score changes for question 330900 on assignment 136934. Exports an audit CSV to S3. No database writes.';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * ASSUMPTIONS — verify these against your actual schema before running:
     *
     *  SubmissionFile  — user_id, assignment_id, question_id, score
     *  Score           — user_id, assignment_id, score  (assignment-level total)
     *  User            — id, email, first_name, last_name, student_id
     */
    public function handle()
    {
        try {
            $assignmentId = 136934;
            $questionId   = 330900;
            $multiplier   = 10;

            $this->info("Fetching submission files for assignment {$assignmentId}, question {$questionId}...");

            $submissionFiles = SubmissionFile::where('assignment_id', $assignmentId)
                ->where('question_id', $questionId)
                ->get();

            if ($submissionFiles->isEmpty()) {
                $this->warn('No submission files found. Exiting.');
                return 0;
            }

            $this->info("{$submissionFiles->count()} submission file(s) found.");

            // Index assignment-level scores by user_id for quick lookup
            $scores = Score::where('assignment_id', $assignmentId)
                ->get()
                ->keyBy('user_id');

            // Pull all relevant users in one query
            $userIds = $submissionFiles->pluck('user_id')->unique();
            $users   = User::whereIn('id', $userIds)->get()->keyBy('id');

            $csvRows   = [];
            $csvRows[] = [
                'user_id',
                'email',
                'name',
                'student_id',
                'old_question_score',
                'new_question_score',
                'old_assignment_score',
                'new_assignment_score',
            ];

            // Group by user so we handle multiple submission files per user cleanly
            foreach ($submissionFiles->groupBy('user_id') as $userId => $userFiles) {
                $user  = $users->get($userId);
                $score = $scores->get($userId);

                if (!$score) {
                    $this->warn("No Score row found for user_id={$userId} — they will appear with N/A assignment scores in the CSV.");
                }

                $oldQuestionScore = round($userFiles->sum('score'), 2);         // ← verify field name
                $newQuestionScore = round($oldQuestionScore * $multiplier, 2);
                $delta            = $newQuestionScore - $oldQuestionScore;

                $oldAssignmentScore = $score ? round($score->score, 2) : 'N/A'; // ← verify field name
                $newAssignmentScore = $score ? round($score->score + $delta, 2) : 'N/A';

                $name = ($user->last_name ?? '') . ', ' . ($user->first_name ?? '');

                $csvRows[] = [
                    $userId,
                    $user->email      ?? 'N/A',
                    trim($name, ', ') ?: 'N/A',
                    $user->student_id ?? 'N/A',                                 // ← verify field name
                    $oldQuestionScore,
                    $newQuestionScore,
                    $oldAssignmentScore,
                    $newAssignmentScore,
                ];
            }

            $filename = "fix_points_{$assignmentId}_q{$questionId}_dryrun.csv";
            $this->uploadCsvToS3($csvRows, $filename);
            $this->info('Done. No database rows were modified.');

        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    /**
     * Build CSV in memory and upload to S3 explicitly (not the default disk).
     * Nothing is written to the local filesystem — safe for Laravel Vapor.
     */
    private function uploadCsvToS3(array $rows, string $filename): void
    {
        $buffer = fopen('php://memory', 'r+');
        foreach ($rows as $row) {
            fputcsv($buffer, $row);
        }
        rewind($buffer);
        $csvContent = stream_get_contents($buffer);
        fclose($buffer);

        $path = 'exports/' . $filename;
        Storage::disk('s3')->put($path, $csvContent);

        $this->info("CSV uploaded to: " . Storage::disk('s3')->url($path));
    }
}
