<?php

namespace App\Console\Commands\Database;

use Exception;
use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class downloadDBFromS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:dbFromS3';

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
            $command = "/Users/franciscaparedes/backup_scripts/send_backup_db_to_s3.sh ~/.ssh/vapor-jump-production ec2-user@ec2-54-183-249-159.us-west-1.compute.amazonaws.com libretexts/db_backups /Users/franciscaparedes/adapt_backups/db_backups adapt_live";
            exec($command, $output, $returnVar);
            if ($returnVar) {
                throw new Exception("Error downloading DB from S3: $returnVar");
            }
        } catch (Exception $e) {
            echo $e->getMessage();

            Telegram::sendMessage([
                'chat_id' => config('myconfig.telegram_channel_id'),
                'parse_mode' => 'HTML',
                'text' => "Error downloading Vapor DB: " . $e->getMessage()
            ]);
            return 1;
        }
        return 0;
    }
}
