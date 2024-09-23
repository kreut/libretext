<?php

namespace App\Console\Commands\OneTimers;

use App\DataShop;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class anonymizeDataShopFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonymize:datashopFiles';

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
     * @param DataShop $dataShop
     * @return int
     */
    public function handle(DataShop $dataShop): int
    {

        ini_set('memory_limit', '-1');

        // Directory path
        $directory = '/Users/franciscaparedes/Downloads/datashops';
        $new_directory = '/Users/franciscaparedes/Downloads/datashops-anon';
        // Get all CSV files in the directory
        $files = glob($directory . '/*.csv');

        // Array to store all unique emails
        $uniqueEmails = [];

        // First pass: collect all unique emails
        foreach ($files as $file) {
            $this->info($file);
            $fileHandle = fopen($file, 'r');

            // Read the header row
            $headers = fgetcsv($fileHandle);

            while (($record = fgetcsv($fileHandle)) !== false) {
                // Combine headers and current row into an associative array
                $recordAssoc = array_combine($headers, $record);

                // Get the anon_student_id (email) and add to the unique email list
                $email = $recordAssoc['anon_student_id'];
                if (!in_array($email, $uniqueEmails)) {
                    $uniqueEmails[] = $email;
                }
            }

            fclose($fileHandle);
        }

        // Query the database for all users with these emails
        $userEmails = DB::table('users')
            ->whereIn('email', $uniqueEmails)
            ->pluck('id', 'email') // This returns an associative array [email => id]
            ->toArray();

        // Second pass: write the updated data to new CSV files
        foreach ($files as $file) {
            $fileHandle = fopen($file, 'r');

            // Read the header row
            $headers = fgetcsv($fileHandle);

            // Store updated records
            $updatedRecords = [];

            while (($record = fgetcsv($fileHandle)) !== false) {
                // Combine headers and current row into an associative array
                $recordAssoc = array_combine($headers, $record);

                // Get the anon_student_id (email)
                $email = $recordAssoc['anon_student_id'];
                // Check if the email has a corresponding user ID
                if (isset($userEmails[$email])) {
                    $domain = substr($email, strrpos($email, '@') + 1);
                    $recordAssoc['anon_student_id'] = "{$userEmails[$email]}@$domain";
                } else {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $recordAssoc['anon_student_id'] = 'unknown';
                    } else {
                        //do nothing
                        $this->info($email);
                    }
                }

                // Add the updated record to the list
                $updatedRecords[] = $recordAssoc;
            }

            fclose($fileHandle);

            // Create the new filename with '-anon' appended
            $newFilename = str_replace('.csv', '-anon.csv', basename($file));
            $newFilePath = $new_directory . '/' . $newFilename;

            // Write to the new CSV file
            $newFileHandle = fopen($newFilePath, 'w');

            // Write headers to the new file
            fputcsv($newFileHandle, $headers);

            // Write the updated records
            foreach ($updatedRecords as $updatedRecord) {
                fputcsv($newFileHandle, $updatedRecord);
            }

            fclose($newFileHandle);

            $this->info("Processed file: $file -> $newFilePath");
        }

        $this->info('All files have been processed.');
        return 0;
    }

}
