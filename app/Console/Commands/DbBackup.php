<?php

namespace App\Console\Commands;

use Exception;
use App\Exceptions\Handler;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
        try {
            $database_name = DB::connection()->getDatabaseName();
            $database_host = config('myconfig.db_host');
            $filename = $database_name . "-" . Carbon::now()->format('Y-m-d_g_i_s_a') . ".sql";
            echo "Backing up $filename\r\n";
            $command = "mysqldump -h $database_host --port=25060 --set-gtid-purged=OFF production_libretexts | gzip > " . storage_path() . "/db_backups/" . $filename . ".gz";
            $returnVar = NULL;
            $output = NULL;
            exec($command, $output, $returnVar);
            if ($returnVar) {
                throw new Exception ("Database backup not successful: $returnVar");
            }
            $db_backup = file_get_contents(storage_path() . "/db_backups/" . $filename . ".gz");
            Storage::disk('s3')->put("db_backups/" . $filename . ".gz", $db_backup, ['StorageClass' => 'STANDARD_IA']);

            echo "Done.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
