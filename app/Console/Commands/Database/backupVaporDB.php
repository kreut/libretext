<?php

namespace App\Console\Commands\Database;

use Exception;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class backupVaporDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:VaporDB';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup Vapor database to my local machine; change local directory as needed and get key';

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

        ini_set('memory_limit', '1024MB');

        try {
            $start_time = microtime(true);
            $backup_path = '/Users/franciscaparedes/adapt_backups/db_backups/';

            $key = new RSA();
            $key->loadKey(file_get_contents('/Users/franciscaparedes/.ssh/vapor-jump-production'));
//ssh -i ~/.ssh/vapor-jump-production ec2-user@ec2-54-183-249-159.us-west-1.compute.amazonaws.com
//Remote server's ip address or hostname
            $ssh = new SSH2('ec2-54-183-249-159.us-west-1.compute.amazonaws.com');

            if (!$ssh->login('ec2-user', $key)) {
                exit('Login Failed');
            }
            echo "Dumping DB\r\n";
            echo $ssh->exec('mysqldump --single-transaction --quick -h production.cluster-civ8gz4roxix.us-west-1.rds.amazonaws.com -u vapor vapor | gzip > dump.sql.gz');

            $ssh = new SSH2('ec2-54-183-249-159.us-west-1.compute.amazonaws.com');

            if (!$ssh->login('ec2-user', $key)) {
                exit('Login Failed');
            }

            echo "Downloading DB\r\n";
            shell_exec('scp -i ~/.ssh/vapor-jump-production ec2-user@ec2-54-183-249-159.us-west-1.compute.amazonaws.com:dump.sql.gz ' . $backup_path . '"$(date +%Y-%m-%d)".sql.gz');

            $total_time = microtime(true) - $start_time;

            foreach (new \DirectoryIterator($backup_path) as $item) {
                if ($item->isFile() && (empty($file) || $item->getMTime() > $file->getMTime())) {
                    if (strpos($item->getPathname(), 'analytics') === false) {
                        $file = clone $item;
                    }
                }
            }

            $newest_file = $file->getPathname();

            $filesize = filesize($newest_file); // bytes
            $filesize = round($filesize / 1000 / 1000, 2) . ' mb';


            Telegram::sendMessage([
                'chat_id' => config('myconfig.telegram_channel_id'),
                'parse_mode' => 'HTML',
                'text' => "DB backup: " . $filesize . ' ' . round($total_time, 1) . ' seconds'
            ]);
            echo "Done!\r\n";
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();

            Telegram::sendMessage([
                'chat_id' => config('myconfig.telegram_channel_id'),
                'parse_mode' => 'HTML',
                'text' => "Error backing up VaporDB: " . $e->getMessage()
            ]);
            return 1;
        }

    }
}
