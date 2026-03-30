<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
use App\QuestionRevision;
use App\Services\WebworkMacroService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill the question_webwork_macros table for all existing WeBWork questions.
 *
 * Run once after deploying the migration:
 *   php artisan macros:backfill-question-macros
 */
class BackfillQuestionWebworkMacros extends Command
{
    protected $signature   = 'macros:backfill-question-macros {--dry-run : Show what would be inserted without writing}';
    protected $description = 'Populate question_webwork_macros for all existing WeBWork questions and their revisions';

    public function handle(WebworkMacroService $macroService): int
    {
        $dryRun = $this->option('dry-run');
        $total  = 0;
        $skipped = 0;

        // ── Process current question source (revision_id = 0) ────────────────
        $this->info('Processing base questions (no revision)…');

        Question::where('technology', 'webwork')
            ->whereNotNull('webwork_code')
            ->chunkById(100, function ($questions) use ($macroService, $dryRun, &$total, &$skipped) {
                foreach ($questions as $question) {
                    $names = $macroService->parseMacroNames($question->webwork_code);
                    if (empty($names)) {
                        $skipped++;
                        continue;
                    }
                    $this->line("  Q#{$question->id}: found macros: " . implode(', ', $names));
                    if (!$dryRun) {
                        $macroService->syncMacrosForQuestion($question->id, 0, $question->webwork_code);
                    }
                    $total++;
                }
            });

        // ── Process question revisions ────────────────────────────────────────
        $this->info('Processing question revisions…');

        QuestionRevision::where('technology', 'webwork')
            ->whereNotNull('webwork_code')
            ->chunkById(100, function ($revisions) use ($macroService, $dryRun, &$total, &$skipped) {
                foreach ($revisions as $revision) {
                    $names = $macroService->parseMacroNames($revision->webwork_code);
                    if (empty($names)) {
                        $skipped++;
                        continue;
                    }
                    $this->line("  Q#{$revision->question_id} Rev#{$revision->id}: found macros: " . implode(', ', $names));
                    if (!$dryRun) {
                        $macroService->syncMacrosForQuestion($revision->question_id, $revision->id, $revision->webwork_code);
                    }
                    $total++;
                }
            });

        $mode = $dryRun ? '[DRY RUN] Would have synced' : 'Synced';
        $this->info("{$mode} macros for {$total} question(s)/revision(s). Skipped {$skipped} with no managed macros.");

        return 0;
    }
}
