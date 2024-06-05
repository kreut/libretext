<?php

namespace App\Console\Commands;

use DirectoryIterator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Laravel\Facades\Telegram;

class backupS3Files extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 's3:backup';

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

    function sendTelegramMessage($message)
    {
        Telegram::sendMessage([
            'chat_id' => config('myconfig.telegram_channel_id'),
            'parse_mode' => 'HTML',
            'text' => $message
        ]);

    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Throwable
     */
    public function handle()
    {

        try {
            $command = "/usr/local/bin/aws s3 ls s3://libretexts/assignments/ --profile default --output json";
            exec($command, $output, $return_var);
            if ($return_var !== 0) {
                $this->sendTelegramMessage("Unable to get the assignments for the s3 backup with return var $return_var");
                exit;
            }
            $s3_assignments = [];
            foreach ($output as $value) {
                $value = str_replace('PRE', '', $value);
                $value = str_replace(' ', '', $value);
                $folder = str_replace('/', '', $value);
                $s3_assignments[] = $folder;
            }
            $local_assignments = [];
            $iterator = new DirectoryIterator('/var/www/dev.adapt/storage/s3_backups/assignments');
            foreach ($iterator as $fileinfo) {
                if (!$fileinfo->isDot()) {
                    $local_assignments[$fileinfo->getBasename()] = $fileinfo->getPathname();
                }
            }
            echo "Removing local assignments\r\n";
            foreach ($local_assignments as $assignment_id => $local_assignment) {
                if (!in_array($assignment_id, $s3_assignments)) {

                    $command = "rm -rf $local_assignment";
                    exec($command, $output, $return_var);
                    echo "Removed Local $local_assignment\r\n";
                    if ($return_var !== 0) {
                        $this->sendTelegramMessage("Unable to remove folder from Local: Assignment $assignment_id");
                        exit;
                    }
                    $command = "/usr/local/bin/aws s3 rm s3://adapt/assignments/$assignment_id --recursive --endpoint=https://sfo3.digitaloceanspaces.com  --profile digital_ocean";
                    exec($command, $output, $return_var);
                    if ($return_var !== 0) {
                        $this->sendTelegramMessage("Unable to remove folder from Digital Ocean: Assignment $assignment_id");
                        exit;
                    }
                    echo "Removed Digital Ocean $local_assignment\r\n";
                }
            }
            echo "Syncing from S3\r\n";
            $command = '/usr/local/bin/aws s3 sync s3://libretexts /var/www/dev.adapt/storage/s3_backups --profile default --exclude "db_backups/*"';
            exec($command, $output, $return_var);
            if ($return_var !== 0) {
                $this->sendTelegramMessage("Unable to get the folders for the s3 backup with return var $return_var");
                exit;
            }
            echo "Backing up to Digital Ocean Spaces\r\n";
            $command = "/usr/local/bin/aws s3 sync /var/www/dev.adapt/storage/s3_backups s3://adapt --endpoint=https://sfo3.digitaloceanspaces.com  --profile digital_ocean";
            exec($command, $output, $return_var);
            echo "Return var: $return_var\r\n";
            if ($return_var !== 0) {
                $this->sendTelegramMessage("Unable to sync back to Digital Ocean Spaces with return var $return_var");
                exit;
            }

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
