<?php

namespace App\Console\Commands\Flashcards;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class addLanguageToFlashcards extends Command
{
    protected $signature = 'flashcards:assign-language';
    protected $description = 'Assign front/back caption languages to flashcard questions based on the editor user ID, and write results to a CSV';

    // These editors write Spanish on both sides
    const SPANISH_USER_IDS = [18925, 7420, 321183];

    public function handle()
    {
        try {
            $revisions = DB::table('question_revisions')
                ->rightJoin('questions', 'questions.id', '=', 'question_revisions.question_id')
                ->where('questions.qti_json_type', 'flashcard')
                ->orderBy('questions.id')
                ->get([
                    'questions.id as question_id',
                    'question_revisions.id as revision_id',
                    DB::raw('COALESCE(question_revisions.qti_json, questions.qti_json) as qti_json'),
                    'questions.question_editor_user_id',
                ]);

            if ($revisions->isEmpty()) {
                $this->line('No flashcard questions found.');
                return 0;
            }

            $this->line("Found {$revisions->count()} flashcard question(s).\n");

            $handle = fopen('php://memory', 'w');
            // UTF-8 BOM so Excel and other tools handle Spanish characters correctly
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'question_id',
                'revision_id',
                'question_editor_user_id',
                'side',
                'type',
                'text',
                'assigned_language',
            ]);

            foreach ($revisions as $revision) {
                $qtiJson = is_string($revision->qti_json)
                    ? json_decode($revision->qti_json, true)
                    : (array) $revision->qti_json;

                $card = $qtiJson['card'] ?? [];
                $isSpanishEditor = in_array((int) $revision->question_editor_user_id, self::SPANISH_USER_IDS);

                foreach (['front', 'back'] as $side) {
                    $type = $card["{$side}Type"] ?? '';

                    if (!in_array($type, ['text_only', 'text_media'])) {
                        continue;
                    }

                    $text = trim($side === 'front'
                        ? ($card['term']   ?? '')
                        : ($card['answer'] ?? ''));

                    $language = $isSpanishEditor ? 'Spanish' : 'English';

                    fputcsv($handle, [
                        $revision->question_id,
                        $revision->revision_id ?? 'none',
                        $revision->question_editor_user_id,
                        $side,
                        $type,
                        $text,
                        $language,
                    ]);
                }
            }

            rewind($handle);
            $s3Key = 'flashcard_languages.csv';
            Storage::disk('s3')->put($s3Key, stream_get_contents($handle));
            fclose($handle);

            $this->line("Done. CSV uploaded to S3: {$s3Key}");

        } catch (Exception $e) {
            $this->line('Error: ' . $e->getMessage());
        }

        return 0;
    }
}
