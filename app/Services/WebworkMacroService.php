<?php

namespace App\Services;

use App\WebworkMacro;
use Illuminate\Support\Facades\DB;

class WebworkMacroService
{
    /**
     * Parse a WeBWork source string and return all .pl macro filenames
     * that appear in loadMacros(...) calls AND exist in the webwork_macros table.
     */
    public function parseMacroNames(string $source_code): array
    {
        preg_match_all('/loadMacros\s*\((.*?)\)/s', $source_code, $matches);

        $found = [];
        foreach ($matches[1] as $args_block) {
            preg_match_all('/["\']([^"\']+\.pl)["\']/i', $args_block, $name_matches);
            foreach ($name_matches[1] as $name) {
                $found[] = $name;
            }
        }

        return array_unique($found);
    }

    /**
     * After a question is saved, sync the question_webwork_macros table.
     * Only records macros that exist in the webwork_macros table.
     */
    public function syncMacrosForQuestion(int $question_id, int $question_revision_id, string $source_code): void
    {
        $parsed_names = $this->parseMacroNames($source_code);

        if (empty($parsed_names)) {
            return;
        }

        $managed_macros = WebworkMacro::whereIn('name', $parsed_names)
            ->pluck('id', 'name');

        if ($managed_macros->isEmpty()) {
            return;
        }

        DB::table('question_webwork_macros')
            ->where('question_id', $question_id)
            ->where('question_revision_id', $question_revision_id)
            ->delete();

        $now = now();
        $rows = [];
        foreach ($managed_macros as $name => $macro_id) {
            $rows[] = [
                'question_id'          => $question_id,
                'question_revision_id' => $question_revision_id,
                'webwork_macro_id'     => $macro_id,
                'created_at'           => $now,
                'updated_at'           => $now,
            ];
        }

        DB::table('question_webwork_macros')->insert($rows);
    }

    /**
     * Check whether a macro is referenced in any question or revision.
     */
    public function macroIsInUse(int $webwork_macro_id): bool
    {
        return DB::table('question_webwork_macros')
            ->where('webwork_macro_id', $webwork_macro_id)
            ->exists();
    }

    /**
     * Return a human-readable summary of where a macro is used.
     */
    public function usageSummary(int $webwork_macro_id): string
    {
        $count = DB::table('question_webwork_macros')
            ->where('webwork_macro_id', $webwork_macro_id)
            ->distinct('question_id')
            ->count('question_id');

        $plural = $count === 1 ? 'question' : 'questions';
        return "This macro is referenced in {$count} {$plural} and cannot be deleted.";
    }
}
