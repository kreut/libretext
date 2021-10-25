<?php

namespace App\Console\Commands\Database;

use App\DataShop;
use Exception;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

        ini_set('memory_limit', '1024MB');

        try {
            $start_time = microtime(true);
            $backup_path = storage_path() . '/app/db_backups/';

            $db_name = 'libretext';
            $conf_file = '/Users/franciscaparedes/itsmorethanatextbook/website/local_config_stuff/config.conf';

            $key = new RSA();
            $key->loadKey(file_get_contents('/Users/franciscaparedes/.ssh/vapor-jump-production'));

//Remote server's ip address or hostname
            $ssh = new SSH2('ec2-54-183-249-159.us-west-1.compute.amazonaws.com');

            if (!$ssh->login('ec2-user', $key)) {
                exit('Login Failed');
            }
            echo $ssh->exec('mysqldump --single-transaction --quick -h production.cluster-civ8gz4roxix.us-west-1.rds.amazonaws.com -u vapor vapor --ignore-table=vapor.data_shops | gzip > dump.sql.gz');
            $ssh = new SSH2('ec2-54-183-249-159.us-west-1.compute.amazonaws.com');

            if (!$ssh->login('ec2-user', $key)) {
                exit('Login Failed');
            }
            echo $ssh->exec('mysqldump --single-transaction --quick -h production.cluster-civ8gz4roxix.us-west-1.rds.amazonaws.com -u vapor vapor data_shops | gzip > analytics.sql.gz');
            shell_exec('scp -i ~/.ssh/vapor-jump-production ec2-user@ec2-54-183-249-159.us-west-1.compute.amazonaws.com:analytics.sql.gz ' . $backup_path . 'analytics.sql.gz');
            echo "Downloaded DB\r\n";
            shell_exec('scp -i ~/.ssh/vapor-jump-production ec2-user@ec2-54-183-249-159.us-west-1.compute.amazonaws.com:dump.sql.gz ' . $backup_path . '"$(date +%Y-%m-%d)".sql.gz');
            echo "Downloaded data_shops\r\n";

            exec("gunzip -f {$backup_path}analytics.sql.gz", $output, $return_value);
            if ($return_value) {
                throw new Exception('Could not unzip');
            }
            echo "Unzipped data_shops\r\n";
            exec("/Applications/MAMP/Library/bin/mysql --defaults-file={$conf_file} '{$db_name}' < {$backup_path}analytics.sql", $output, $return_value);
            if ($return_value) {
                throw new Exception();
            }
            echo "Imported data_shops\r\n";

            $data_shops = DataShop::all();
            $columns = Schema::getColumnListing('data_shops');
            $csv = fopen("{$backup_path}analytics.csv", 'w');
            fputcsv($csv, $columns);
            foreach ($data_shops as $row) {
                fputcsv($csv, $row->toArray());
            }
            fclose($csv);
            echo "Wrote to CSV data_shops\r\n";
            exec("cd {$backup_path};zip -j analytics.zip analytics.csv", $output, $return_value);
            if ($return_value) {
                throw new Exception('Could not zip analytics');
            }
            echo "Zipped analytics file\r\n";
            $analytics = file_get_contents($backup_path . 'analytics.zip');

            Storage::disk('backup_s3')->put('analytics.zip', $analytics);
            echo "Sent data_shops to S3\r\n";
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
