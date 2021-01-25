<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

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

        $filename = env('DB_DATABASE') . "-" . Carbon::now()->format('Y-m-d_g_i_s_a') . ".sql";
        echo "Backing up $filename\r\n";
        $command = "mysqldump ".env('DB_DATABASE'). " | gzip > " . storage_path() . "/db_backups/". $filename . ".gz";
        $returnVar = NULL;
        $output  = NULL;
        exec($command, $output, $returnVar);
        $db_backup = file_get_contents(storage_path() . "/db_backups/". $filename . ".gz");
        Storage::disk('s3')->put("db_backups/". $filename . ".gz", $db_backup, ['StorageClass' => 'STANDARD_IA']);

        echo "Done.";
    }
}
