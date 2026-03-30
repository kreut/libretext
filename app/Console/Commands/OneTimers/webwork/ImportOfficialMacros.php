<?php

namespace App\Console\Commands\OneTimers\webwork;

use App\WebworkMacro;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Import official PG macros from pg_macros.xlsx on S3.
 *
 * Spreadsheet columns: Category | File | Summary | Source
 * Maps to webwork_macros:  (ignored) | name | description | macro
 *
 * Idempotent — uses updateOrCreate so it is safe to re-run.
 *
 * Usage:
 *   php artisan macros:import-official
 *   php artisan macros:import-official --dry-run
 */
class ImportOfficialMacros extends Command
{
    protected $signature   = 'macros:import-official
                              {--dry-run : Preview without writing to the database}';
    protected $description = 'Import official PG macros from pg_macros.xlsx on S3';

    public function handle(): int
    {
        $s3Path = 'pg_macros.csv';
        $dryRun = $this->option('dry-run');

        $this->info("Reading s3://{$s3Path} …");

        if (!Storage::disk('s3')->exists($s3Path)) {
            $this->error("File not found on S3: {$s3Path}");
            return 1;
        }

        // Download to a temp file so PhpSpreadsheet can open it
        $stream = Storage::disk('s3')->readStream('pg_macros.csv');

        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            $rows[] = $row;
        }

        fclose($stream);

        array_shift($rows);


        // Remove header row (Category | File | Summary | Source)
        array_shift($rows);

        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            // Column indices: 0 = Category, 1 = File (name), 2 = Summary, 3 = Source (url)
            $name        = trim((string) ($row[1] ?? ''));
            $description = trim((string) ($row[2] ?? ''));
            $macro       = trim((string) ($row[3] ?? ''));

            if (!$name) {
                $skipped++;
                continue;
            }

            $this->line("  {$name}");

            if (!$dryRun) {
                WebworkMacro::updateOrCreate(
                    [
                        'name'   => $name,
                        'source' => 'official',
                    ],
                    [
                        'user_id'     => null,
                        'source'      => 'official',
                        'description' => $description,
                        'macro'       => $macro,
                    ]
                );
            }

            $imported++;
        }

        $mode = $dryRun ? '[DRY RUN] Would import/update' : 'Imported/updated';
        $this->info("{$mode} {$imported} official macro(s). Skipped {$skipped} blank row(s).");

        return 0;
    }
}
