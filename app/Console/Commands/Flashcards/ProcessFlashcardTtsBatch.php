<?php

namespace App\Console\Commands\Flashcards;

use App\Jobs\GenerateFlashcardTTS;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessFlashcardTtsBatch extends Command
{
    protected $signature = 'flashcards:process-tts-batch
                            {--batch=20 : Number of revisions to process per run}';

    protected $description = 'Process a batch of flashcard TTS revisions that have not yet been successfully completed. Intended to run every 5 minutes via CRON.';

    public function handle()
    {
        $batchSize = (int)$this->option('batch');

        // ── Load CSV ─────────────────────────────────────────────────────────
        $csvContent = Storage::disk('s3')->get('flashcard_languages.csv');
        if (!$csvContent) {
            $this->line('CSV file not found on S3.');
            return 1;
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csvContent);
        rewind($handle);
        $header = fgetcsv($handle);
        $questionIdCol = array_search('question_id', $header);
        $revisionIdCol = array_search('revision_id', $header);
        $csvRows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $current = [
                'question_id' => $row[$questionIdCol],
                'question_revision_id' => $row[$revisionIdCol] === 'none' ? 0 : $row[$revisionIdCol],
            ];
            if (!in_array($current, $csvRows)) {
                $csvRows[] = $current;
            }
        }
        // ── Exclude already processed ─────────────────────────────────────────
        $doneOrProcessing = DB::table('flashcard_ai_audio_logs')
            ->where('job_type', 'tts')
            ->whereIn('status', ['success', 'processing', 'error'])
            ->get(['question_id', 'question_revision_id'])
            ->toArray();
        $done_or_processing_arr = [];
        foreach ($doneOrProcessing as $value) {
            $done_or_processing_arr[] = ['question_id' => $value->question_id, 'question_revision_id' => $value->question_revision_id];
        }
        foreach ($csvRows as $key => $value) {
            $value = ['question_id' => $value['question_id'], 'question_revision_id' => $value['question_revision_id']];
            if (in_array($value, $done_or_processing_arr)) {
                unset($csvRows[$key]);
            }
        }
        $csvRows = array_slice(array_values($csvRows), 0, $batchSize);
        if (!count($csvRows)) {
            $this->line('No pending flashcard questions found.');
            return 0;
        }

        $total = count($csvRows);
        $succeeded = 0;
        $failed = 0;
        $times = [];

        $this->line("Processing {$total} question(s)...\n");
        foreach ($csvRows as $i => $item) {
            $num = $i + 1;
            $questionId = $item['question_id'];
            $revisionId = $item['question_revision_id'];

            $this->line("[{$num}/{$total}] Question {$questionId} / Revision {$revisionId}...");

            $start = microtime(true);
            try {
                $job = new GenerateFlashcardTTS($questionId, $revisionId);
                $job->handle();
                $elapsed = round(microtime(true) - $start, 1);
                $times[] = $elapsed;
                $this->line("  ✓ Done ({$elapsed}s)");
                $succeeded++;
            } catch (Exception $e) {
                $elapsed = round(microtime(true) - $start, 1);
                $this->line("  ✗ Failed ({$elapsed}s): " . $e->getMessage());
                $failed++;
            }
        }

        $avg = count($times) > 0
            ? round(array_sum($times) / count($times), 1)
            : 0;

        $this->line("\nComplete.");
        $this->line("  Succeeded    : {$succeeded}");
        $this->line("  Failed       : {$failed}");
        $this->line("  Avg per card : {$avg}s");

        return $failed > 0 ? 1 : 0;
    }
}
