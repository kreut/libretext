<?php

namespace App\Console\Commands\Database;


use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class importLatest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:latest';

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


    public function handle(): int
    {
        try {

            $backup_path = '/Users/franciscaparedes/adapt_backups/db_backups/';

            $date = Carbon::now()->format('Y-m-d');
            $local_db_backup_filename = $backup_path . $date . '.sql';
            exec("gunzip -f $local_db_backup_filename.gz", $output, $return_value);
            if ($return_value) {
                throw new Exception ("not unzipped");
            }
            $conf_file = '/Users/franciscaparedes/itsmorethanatextbook/website/local_config_stuff/config.conf';
            exec("/Applications/MAMP/Library/bin/mysql --defaults-file=$conf_file --execute='DROP DATABASE libretext';", $output, $return_value);
            if ($return_value) {
                throw new Exception ("not dropped");
            }
            exec("/Applications/MAMP/Library/bin/mysql --defaults-file=$conf_file --execute='CREATE DATABASE libretext;'", $output, $return_value);
            if ($return_value) {
                throw new Exception ("not created");
            }
            exec("/Applications/MAMP/Library/bin/mysql --defaults-file=$conf_file 'libretext' < $local_db_backup_filename", $output, $return_value);
            if ($return_value) {
                throw new Exception ("not imported");
            }
            echo "Imported!";
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();

            return 1;
        }

    }
}
