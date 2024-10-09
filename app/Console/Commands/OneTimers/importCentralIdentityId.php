<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class importCentralIdentityId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:centralIdentityId';

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
    public function handle()
    {
        try {
            $csvFile = '/Users/franciscaparedes/Downloads/users_202410071309.csv';

            // Open the file
            if (($handle = fopen($csvFile, 'r')) !== false) {
                // Skip the header row
                fgetcsv($handle);

                // Loop through each row of the CSV file
                while (($row = fgetcsv($handle)) !== false) {
                    // Insert data into the database
                    DB::table('email_central_identity_id')->insert([
                        'central_identity_id' => $row[0],
                        'email' => $row[1],
                    ]);
                }

                // Close the file
                fclose($handle);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return 1;

        }
        echo "CSV file imported successfully!";
        return 0;
    }
}
