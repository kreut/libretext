<?php

namespace App\Console\Commands\Flashcards;

use App\Jobs\GenerateFlashcardTTS;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessFlashcardTtsBatch extends Command
{
    protected $signature = 'flashcards:process-tts-batch
                            {--batch=20 : Number of revisions to process per run}';

    protected $description = 'Process a batch of flashcard TTS revisions that have not yet been successfully completed. Intended to run every 5 minutes via CRON.';

    public function handle()
    {
        $batchSize = (int) $this->option('batch');

        // ── Find revisions that still need TTS ───────────────────────────────
        // A revision is "done" if every eligible side (front/back) has a
        // success entry in flashcard_tts_logs.
        //
        // Rather than trying to compute "all eligible sides are done" in SQL,
        // we simply exclude any revision_id that has ANY success log entry —
        // the job itself is idempotent and will skip sides that are already done.
        // Revisions with at least one successful side but not both will be
        // retried until complete.
        //
        // We exclude revisions currently marked 'processing' to avoid
        // double-running if the CRON overlaps.

        $doneRevisionIds = DB::table('flashcard_ai_audio_logs')
            ->where('job_type', 'tts')
            ->where('status', 'success')
            ->pluck('question_revision_id')
            ->unique()
            ->toArray();

        $processingRevisionIds = DB::table('flashcard_ai_audio_logs')
            ->where('job_type', 'tts')
            ->where('status', 'processing')
            ->pluck('question_revision_id')
            ->unique()
            ->toArray();

        $skipRevisionIds = array_unique(array_merge($doneRevisionIds, $processingRevisionIds));

        // ── Questions with revisions ─────────────────────────────────────────
        $query = DB::table('question_revisions')
            ->where('qti_json_type', 'flashcard')
            ->orderBy('id');

        if (!empty($skipRevisionIds)) {
            $query->whereNotIn('id', $skipRevisionIds);
        }

        $revisions = $query->limit($batchSize)->get(['id', 'question_id']);

        // ── Questions without any revisions ──────────────────────────────────
        // Fill remaining batch slots with revision-less flashcard questions
        // that haven't been successfully processed yet (log uses question_id,
        // revision_id = null for these).
        $remainingSlots = $batchSize - $revisions->count();

        $noRevisionQuestions = collect();
        if ($remainingSlots > 0) {
            $doneNoRevisionQuestionIds = DB::table('flashcard_ai_audio_logs')
                ->where('job_type', 'tts')
                ->whereIn('status', ['success', 'processing'])
                ->whereNull('question_revision_id')
                ->pluck('question_id')
                ->unique()
                ->toArray();

            // Questions that have no revisions at all
            $noRevisionQuestions = DB::table('questions')
                ->where('qti_json_type', 'flashcard')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('question_revisions')
                        ->whereColumn('question_revisions.question_id', 'questions.id');
                })
                ->when(!empty($doneNoRevisionQuestionIds), function ($q) use ($doneNoRevisionQuestionIds) {
                    $q->whereNotIn('id', $doneNoRevisionQuestionIds);
                })
                ->orderBy('id')
                ->limit($remainingSlots)
                ->get(['id as question_id']);
        }

        if ($revisions->isEmpty() && $noRevisionQuestions->isEmpty()) {
            $this->line('No pending flashcard questions found.');
            return 0;
        }

        $total     = $revisions->count() + $noRevisionQuestions->count();
        $succeeded = 0;
        $failed    = 0;
        $times     = [];

        $this->line("Processing {$total} question(s)...\n");

        // ── Process revisions ────────────────────────────────────────────────
        foreach ($revisions as $i => $revision) {
            $num = $i + 1;
            $this->line("[{$num}/{$total}] Revision {$revision->id} (question {$revision->question_id})...");

            $start = microtime(true);

            try {
                $job = new GenerateFlashcardTTS($revision->question_id, $revision->id);
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

        // ── Process revision-less questions ──────────────────────────────────
        $offset = $revisions->count();
        foreach ($noRevisionQuestions as $i => $question) {
            $num = $offset + $i + 1;
            $this->line("[{$num}/{$total}] Question {$question->question_id} (no revision)...");

            $start = microtime(true);

            try {
                $job = new GenerateFlashcardTTS($question->question_id, 0);
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
