<?php

namespace App\Console\Commands\Database;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class importDatabaseToVapor extends Command

{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:databaseToVapor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump current database and load into another database';

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

        ini_set('memory_limit', '2048MB');

        try {
            echo "Not ready yet.";
            exit;
            $start_time = microtime(true);


            $key = new RSA();

            $key->loadKey(file_get_contents('/Users/franciscaparedes/.ssh/vapor-jump-production'));
            /* ssh in using: ssh -i ~/.ssh/vapor-jump-production ec2-user@ec2-54-183-249-159.us-west-1.compute.amazonaws.com*/
//Remote server's ip address or hostname
            $ssh = new SSH2('ec2-54-183-249-159.us-west-1.compute.amazonaws.com');

            if (!$ssh->login('ec2-user', $key)) {
                exit('Login Failed');
            }

            // echo $ssh->exec('mysqldump --single-transaction --verbose -h production.cluster-civ8gz4roxix.us-west-1.rds.amazonaws.com -u vapor vapor data_shops > data_shops.sql');
            /* echo $ssh->exec('gunzip dmp.sql.gz');
             echo $ssh->exec('ls');
             exit;*/
            $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();

            $mysql8_db_password = config('myconfig.mysql8_db_password');
            //shell_exec('scp -i ~/.ssh/vapor-jump-production /Users/franciscaparedes/adapt_laravel_8/storage/app/db_backups/2024-03-12.sql ec2-user@ec2-54-183-249-159.us-west-1.compute.amazonaws.com:2024-03-12.sql');

            //echo $ssh->exec('ls');
            //Try TO loop through all tables and do it one at a time
            foreach ($tables as $table) {
                $ssh = new SSH2('ec2-54-183-249-159.us-west-1.compute.amazonaws.com');

                if (!$ssh->login('ec2-user', $key)) {
                    exit('Login Failed');
                }
                echo $ssh->exec("mysqldump --single-transaction --quick -h production.cluster-civ8gz4roxix.us-west-1.rds.amazonaws.com -u vapor vapor $table > dump.sql");
                echo "$table\r\n";
                echo $ssh->exec("mysql -h production-mysql-8.civ8gz4roxix.us-west-1.rds.amazonaws.com -u vapor -p$mysql8_db_password vapor < dump.sql");
            }
            $total_time = microtime(true) - $start_time;
            echo $total_time;
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
