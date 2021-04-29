<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Exceptions\Handler;
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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        try {
            $command = "/usr/local/bin/aws s3 ls s3://libretexts --profile default --output json";
            exec($command, $output, $return_var);
            if ($return_var !== 0) {
                Telegram::sendMessage([
                    'chat_id' => config('myconfig.telegram_channel_id'),
                    'parse_mode' => 'HTML',
                    'text' => "Unable to get the folders for the s3 backup with return var $return_var"
                ]);
              exit;
            }
            foreach ($output as $value) {

                $value = str_replace('PRE', '', $value);
                $value = str_replace(' ', '', $value);
                $folder = str_replace('/', '', $value);

                echo "Folder: $folder";
                $local_path = "/var/www/dev.adapt/storage/s3_backups/$folder";
                $command = "/usr/local/bin/aws s3 sync s3://libretexts/$folder $local_path --profile default";
                exec($command, $output, $return_var);
                echo "Return var: $return_var\r\n";
                if ($return_var !== 0) {
                    Telegram::sendMessage([
                        'chat_id' => config('myconfig.telegram_channel_id'),
                        'parse_mode' => 'HTML',
                        'text' => "Unable to sync s3 contents from $folder with return variable $return_var"
                    ]);
                    exit;
                }
            }

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
