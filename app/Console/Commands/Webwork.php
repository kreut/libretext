<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Webwork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:webwork';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stores the webwork questions in the database';

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
     * @return mixed
     */
    public function handle()
    {
        $tables = DB::connection('webwork')->select('SHOW TABLES');
        foreach($tables as $table)
        {
            if (strpos($table->Tables_in_webwork, 'OPL') !== false) {
                echo $table->Tables_in_webwork . "\r\n";
            }
        }
    }
}
