<?php

namespace App\Console\Commands;

use App\DataShop;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class saveDataShopsToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save:dataShopsToS3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        try {
            if (app()->environment() !== 'production') {
                throw new Exception ("Cannot run this script from anywhere but production since the bucket is the backup bucket.");
            }
            $fileName = "data_shops/" . Carbon::now()->format('Y-m-d') . '.csv';

            // Open a new CSV file for writing
            $file = fopen('php://temp', 'w+');

            // Fetch all columns dynamically
            $columns = Schema::getColumnListing('data_shops'); // Assuming DataShop is your model

            // Write the header row with column names
            fputcsv($file, $columns);

            // Calculate the timestamp 24 hours ago
            $timestamp24HoursAgo = Carbon::now()->subHours(24);
            echo "Writing file...\r\n";
            // Query and write rows updated within the last 24 hours into the CSV
            $num = DataShop::where('updated_at', '>=', $timestamp24HoursAgo)->count();
            echo "$num rows in data_shops.\r\n";
            if ($num) {
                DataShop::where('updated_at', '>=', $timestamp24HoursAgo)
                    ->chunk(10000, function ($rows) use ($file) {
                        foreach ($rows as $row) {
                            fputcsv($file, $row->toArray());
                        }
                    });

                // Store the file in the S3 bucket
                echo "Storing file...\r\n";
                Storage::disk('s3')->put($fileName, $file);
                echo "Deleting old records...\r\n";
                DataShop::where('updated_at', '<', Carbon::now()->days(2))->delete();
                // Close the file handle
                fclose($file);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
            return 1;

        }
        return 0;
    }
}
