<?php

namespace App\Console\Commands;


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
    public function handle()
    {

        $start_time = microtime(true);
        $backup_path = storage_path() . '/app/db_backups/';


        $key = new RSA();
        $key->loadKey(file_get_contents('/Users/franciscaparedes/.ssh/vapor-jump-production'));

//Remote server's ip address or hostname
        $ssh = new SSH2('ec2-54-183-249-159.us-west-1.compute.amazonaws.com');

        if (!$ssh->login('ec2-user', $key)) {
            exit('Login Failed');
        }
        echo $ssh->exec('mysqldump --single-transaction --quick -h production.cluster-civ8gz4roxix.us-west-1.rds.amazonaws.com -u vapor vapor | gzip > dump.sql.gz');
        shell_exec('scp -i ~/.ssh/vapor-jump-production ec2-user@ec2-54-183-249-159.us-west-1.compute.amazonaws.com:dump.sql.gz ' . $backup_path . '"$(date +%Y-%m-%d)".sql.gz');
        $total_time = microtime(true) - $start_time;

        foreach (new \DirectoryIterator($backup_path) as $item) {
            if ($item->isFile() && (empty($file) || $item->getMTime() > $file->getMTime())) {
                $file = clone $item;
            }
        }

        $newest_file = $file->getPathname();

        $filesize = filesize( $newest_file); // bytes
        //$filesize = round($filesize / 1024 / 1024, 2) . ' mb';

        Telegram::sendMessage([
            'chat_id' => config('myconfig.telegram_channel_id'),
            'parse_mode' => 'HTML',
            'text' => "DB backup: " . $filesize . ' ' . round($total_time, 1) . ' seconds'
        ]);
        return 0;
    }
}
