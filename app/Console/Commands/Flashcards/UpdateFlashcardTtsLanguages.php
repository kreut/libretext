<?php

namespace App\Console\Commands\Flashcards;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateFlashcardTtsLanguages extends Command
{
    protected $signature = 'flashcards:update-tts-languages';
    protected $description = 'Read flashcard_languages.csv from S3 and patch frontTTSLanguage/backTTSLanguage into question_revisions and questions';

    public function handle()
    {
        // ── Load CSV from S3 ─────────────────────────────────────────────────
        $s3Key = 'flashcard_languages.csv';

        if (!Storage::disk('s3')->exists($s3Key)) {
            $this->line("Error: {$s3Key} not found on S3.");
            return 1;
        }

        $csvContents = Storage::disk('s3')->get($s3Key);

        // Strip UTF-8 BOM if present (3-byte sequence, must use substr not ltrim)
        if (substr($csvContents, 0, 3) === "\xEF\xBB\xBF") {
            $csvContents = substr($csvContents, 3);
        }

        // Normalise line endings (Windows CRLF → LF)
        $csvContents = str_replace("\r\n", "\n", $csvContents);
        $csvContents = str_replace("\r", "\n", $csvContents);

        $lines = array_filter(explode("\n", $csvContents), function ($l) {
            return trim($l) !== '';
        });
        $lines = array_values($lines);

        if (count($lines) < 2) {
            $this->line('CSV is empty or has no data rows.');
            return 1;
        }

        // ── Parse CSV into a map: revision_id => ['front' => lang, 'back' => lang] ──
        $header = array_map('trim', str_getcsv(array_shift($lines)));
        $colIndex = array_flip($header);
        $required = ['question_id', 'revision_id', 'side', 'assigned_language'];
        foreach ($required as $col) {
            if (!isset($colIndex[$col])) {
                $this->line("Error: CSV missing required column '{$col}'.");
                return 1;
            }
        }

        // revisionLanguages: [revision_id => [question_id, front => lang, back => lang]]
        // noRevisionLanguages: [question_id => [front => lang, back => lang]]
        $revisionLanguages = [];
        $noRevisionLanguages = [];

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $csvContents);
        rewind($handle);

        $header = array_map('trim', fgetcsv($handle));
        $colIndex = array_flip($header);

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_map('trim', $row);
            $questionId = (int)($row[$colIndex['question_id']] ?? 0);
            $revisionIdRaw = ($row[$colIndex['revision_id']] ?? '');
            $side = ($row[$colIndex['side']] ?? '');
            $language = ($row[$colIndex['assigned_language']] ?? '');

            if (!$questionId || !in_array($side, ['front', 'back']) || !$language) {
                continue;
            }

            // 'none' means the question has no revisions — update questions table only
            if ($revisionIdRaw === 'none' || (int)$revisionIdRaw === 0) {
                if (!isset($noRevisionLanguages[$questionId])) {
                    $noRevisionLanguages[$questionId] = [];
                }
                $noRevisionLanguages[$questionId][$side] = $language;
                continue;
            }

            $revisionId = (int)$revisionIdRaw;
            $this->line($revisionId);
            if (!isset($revisionLanguages[$revisionId])) {
                $revisionLanguages[$revisionId] = ['question_id' => $questionId];
            }
            $revisionLanguages[$revisionId][$side] = $language;
        }

        if (empty($revisionLanguages) && empty($noRevisionLanguages)) {
            $this->line('No valid rows found in CSV.');
            return 1;
        }

        $this->line('Found ' . count($revisionLanguages) . " revision(s) and " . count($noRevisionLanguages) . " revision-less question(s) to update.\n");

        // ── Process inside a transaction ─────────────────────────────────────
        $updatedRevisions = 0;
        $updatedQuestions = 0;
        $skippedRevisions = 0;
        $errors = [];

        // Track the highest revision_id seen per question_id
        // so we know which revision to use for patching questions table
        $latestRevisionPerQuestion = []; // [question_id => revision_id]
        foreach ($revisionLanguages as $revisionId => $data) {
            $questionId = $data['question_id'];
            if (!isset($latestRevisionPerQuestion[$questionId]) ||
                $revisionId > $latestRevisionPerQuestion[$questionId]) {
                $latestRevisionPerQuestion[$questionId] = $revisionId;
            }
        }

        DB::beginTransaction();
        try {
            foreach ($revisionLanguages as $revisionId => $data) {
                $questionId = $data['question_id'];
                $frontLanguage = $data['front'] ?? null;
                $backLanguage = $data['back'] ?? null;

                // ── Patch question_revisions ──────────────────────────────
                $revision = DB::table('question_revisions')->where('id', $revisionId)->first();

                if (!$revision) {
                    $skippedRevisions++;
                    $errors[] = "Revision ID {$revisionId} not found in question_revisions.";
                    continue;
                }

                $qtiJson = is_string($revision->qti_json)
                    ? json_decode($revision->qti_json, true)
                    : (array)$revision->qti_json;

                if ($frontLanguage) {
                    $qtiJson['card']['frontTTSLanguage'] = $frontLanguage;
                }
                if ($backLanguage) {
                    $qtiJson['card']['backTTSLanguage'] = $backLanguage;
                }

                DB::table('question_revisions')
                    ->where('id', $revisionId)
                    ->update(['qti_json' => json_encode($qtiJson),
                        'updated_at' => now()]);

                $updatedRevisions++;

                // ── Patch questions table (latest revision only) ──────────
                if ($latestRevisionPerQuestion[$questionId] === $revisionId) {
                    $question = DB::table('questions')->where('id', $questionId)->first();

                    if (!$question) {
                        $errors[] = "Question ID {$questionId} not found in questions table.";
                        continue;
                    }

                    $questionQtiJson = is_string($question->qti_json)
                        ? json_decode($question->qti_json, true)
                        : (array)$question->qti_json;

                    if ($frontLanguage) {
                        $questionQtiJson['card']['frontTTSLanguage'] = $frontLanguage;
                    }
                    if ($backLanguage) {
                        $questionQtiJson['card']['backTTSLanguage'] = $backLanguage;
                    }

                    DB::table('questions')
                        ->where('id', $questionId)
                        ->update(['qti_json' => json_encode($questionQtiJson),
                            'updated_at' => now()]);

                    $updatedQuestions++;
                }
            }

            // ── Patch questions with no revisions directly ────────────────
            foreach ($noRevisionLanguages as $questionId => $langs) {
                $question = DB::table('questions')->where('id', $questionId)->first();

                if (!$question) {
                    $errors[] = "Question ID {$questionId} (no revision) not found in questions table.";
                    continue;
                }

                $questionQtiJson = is_string($question->qti_json)
                    ? json_decode($question->qti_json, true)
                    : (array)$question->qti_json;

                if (!empty($langs['front'])) {
                    $questionQtiJson['card']['frontTTSLanguage'] = $langs['front'];
                }
                if (!empty($langs['back'])) {
                    $questionQtiJson['card']['backTTSLanguage'] = $langs['back'];
                }

                DB::table('questions')
                    ->where('id', $questionId)
                    ->update(['qti_json' => json_encode($questionQtiJson),
                        'updated_at' => now()]);

                $updatedQuestions++;
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            $this->line('Transaction failed — all changes rolled back.');
            $this->line('Error: ' . $e->getMessage());
            return 1;
        }

        // ── Summary ──────────────────────────────────────────────────────────
        $this->line("Done.");
        $this->line("  Revisions updated : {$updatedRevisions}");
        $this->line("  Questions updated : {$updatedQuestions}");
        $this->line("  Revisions skipped : {$skippedRevisions}");

        if (!empty($errors)) {
            $this->line("\nWarnings:");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }

        return 0;
    }
}
