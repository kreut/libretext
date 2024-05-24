<?php

namespace App\Console\Commands\Analytics;

use DateTime;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class importDataShopsFromS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:dataShopsFromS3';

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
            DB::beginTransaction();
            $files = Storage::disk('s3')->files('data_shops');

            if (empty($files)) {
                return 'No files found in the data_shops bucket.';
            }
            $lastFileName = collect($files)
                ->map(function ($file) {
                    return [
                        'file' => $file,
                        'last_modified' => Storage::disk('s3')->lastModified($file)
                    ];
                })
                ->sortByDesc('last_modified')
                ->first()['file'];

            // Retrieve the contents of the last modified file
            $contents = Storage::disk('s3')->get($lastFileName);
            $max_id = DB::table('data_shops_complete')->max('id');

            $lines = explode(PHP_EOL, $contents);


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
                if ($id > $max_id) {
                    DB::table('data_shops_complete')->insert($rowData);
                    $max_id = $id;
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }

        return 0;
    }
}
