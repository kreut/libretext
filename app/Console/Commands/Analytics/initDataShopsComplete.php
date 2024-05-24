<?php

namespace App\Console\Commands\Analytics;

use DateTime;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

ini_set('memory_limit', '4G');

class initDataShopsComplete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:dataShopsComplete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $files = Storage::disk('s3')->files('data_shops');
            $files = collect($files)
                ->map(function ($file) {
                    return [
                        'file' => $file,
                        'last_modified' => Storage::disk('s3')->lastModified($file)
                    ];
                });

            // Extract dates from file names and sort
            $sorted_files = collect($files)->map(function ($file) {
                // Assuming file names are formatted as 'YYYY-MM-DD.txt'

                // Convert to DateTime for sorting
                return [
                    'file' => $file,
                    'date' => DateTime::createFromFormat('Y-m-d', $file['last_modified'])
                ];
            })->sortBy('last_modified')
                ->pluck('file')
                ->toArray();
            // DB::beginTransaction();
            $max_id = DB::table('data_shops_complete')->max('id');
            foreach ($sorted_files as $file) {
                $filePath = $file->getRealPath();
                $contents = File::get($filePath);

                $lines = explode(PHP_EOL, $contents);
                echo $file->getFileName() . ' ' . count($lines) . "\r\n";
                $headers = str_getcsv(array_shift($lines));

                foreach ($lines as $line) {
                    if (empty(trim($line))) {
                        continue;
                    }
                    $data = str_getcsv($line);

                    $rowData = array_combine($headers, $data);
                    $id = $rowData['id'];
                    foreach (['review_time_start', 'review_time_end', 'due', 'submission_time'] as $key) {
                        if (!$rowData[$key]) {
                            $rowData[$key] = null;
                        }
                    }
                    if ($rowData['updated_at'] === '0000-00-00 00:00:00' && $file->getFileName() === 'analytics.csv') {
                        $rowData['updated_at'] = '2024-03-14 00:00:00';

                    }
                    if ($id > $max_id) {
                        DB::table('data_shops_complete')->insert($rowData);
                        $max_id = $id;
                    } else {
                        echo "$id used\r\n";
                    }
                }
            }
            // DB::commit();
        } catch (Exception $e) {
            // DB::rollback();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
