<?php

namespace App\Console\Commands\OneTimers\webwork;

use App\WebworkMacro;
use App\WebworkMacroRevision;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Sync custom macros from opl.libretexts.org.
 *
 * Steps:
 *   1. Truncate webwork_macro_revisions.
 *   2. Delete all webwork_macros where source = 'custom'.
 *   3. Fetch custom macro list from opl.libretexts.org/api/macros?source_type=custom.
 *   4. For each macro, fetch its content from /api/macros/by-name/{name}.
 *   5. Insert into webwork_macros (source=custom, no user_id, no description, is_retired=0).
 *
 * Idempotent — safe to re-run.
 *
 * Usage:
 *   php artisan macros:sync-custom
 *   php artisan macros:sync-custom --dry-run
 */
class SyncCustomMacros extends Command
{
    protected $signature = 'macros:sync-custom {--dry-run : Preview without writing to the database}';

    protected $description = 'Sync custom macros from opl.libretexts.org into webwork_macros';

    private const LIST_URL    = 'https://opl.libretexts.org/api/macros';
    private const BY_NAME_URL = 'https://opl.libretexts.org/api/macros/by-name/';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // ------------------------------------------------------------------
        // 1. Truncate webwork_macro_revisions
        // ------------------------------------------------------------------
        $this->info('Truncating webwork_macro_revisions…');
        if (!$dryRun) {
            WebworkMacroRevision::truncate();
        }

        // ------------------------------------------------------------------
        // 2. Delete source=custom rows from webwork_macros
        // ------------------------------------------------------------------
        $this->info('Deleting custom macros from webwork_macros…');
        if (!$dryRun) {
            WebworkMacro::where('source', 'custom')->delete();
        }

        // ------------------------------------------------------------------
        // 3. Fetch the list of custom macros
        // ------------------------------------------------------------------
        $this->info('Fetching custom macro list from ' . self::LIST_URL . '…');

        $listResponse = Http::get(self::LIST_URL, ['source_type' => 'custom']);

        if ($listResponse->failed()) {
            $this->error('Failed to fetch macro list: HTTP ' . $listResponse->status());
            return 1;
        }

        $macros = $listResponse->json();

        if (!is_array($macros) || empty($macros)) {
            $this->warn('No custom macros returned from API.');
            return 0;
        }

        $this->info('Found ' . count($macros) . ' custom macro(s). Importing…');

        // ------------------------------------------------------------------
        // 4 & 5. Fetch each macro's content and insert
        // ------------------------------------------------------------------
        $imported = 0;
        $skipped  = 0;

        foreach ($macros as $macro) {
            $name = trim((string) ($macro['name'] ?? ''));

            if (!$name) {
                $this->warn('  Skipping entry with no name.');
                $skipped++;
                continue;
            }

            $this->line("  Fetching: {$name}");

            $contentResponse = Http::get(self::BY_NAME_URL . urlencode($name));

            if ($contentResponse->failed()) {
                $this->warn("  Skipping {$name} — HTTP {$contentResponse->status()}");
                $skipped++;
                continue;
            }

            $macroContent = $contentResponse->body();

            if (!$dryRun) {
                WebworkMacro::create([
                    'user_id'     => null,
                    'source'      => 'custom',
                    'name'        => $name,
                    'description' => '',
                    'macro'       => $macroContent,
                    'is_retired'  => 0,
                ]);
            }

            $imported++;
        }

        $mode = $dryRun ? '[DRY RUN] Would import' : 'Imported';
        $this->info("{$mode} {$imported} custom macro(s). Skipped {$skipped}.");

        return 0;
    }
}
