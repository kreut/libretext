<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Carbon\Carbon;
class DbBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Database Backup';
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

        $filename = env('DB_DATABASE') . "-" . Carbon::now()->format('Y-m-d') . ".sql";
        echo "Backing up $filename\r\n";
        $command = "mysqldump ".env('DB_DATABASE'). " | gzip > " . storage_path() . "/db_backups/". $filename . ".gz";
        $returnVar = NULL;
        $output  = NULL;
        exec($command, $output, $returnVar);
        echo "Done.";
    }
}
