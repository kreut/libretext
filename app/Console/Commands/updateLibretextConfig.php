<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class updateLibretextConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:LibretextConfig';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Libretext config is cached from an S3 file.  This updates the file in the EFS.';

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

        file_put_contents("/mnt/local/libretext.config.php", Storage::disk('s3')->get("libretext.config.php"));
        echo file_get_contents("/mnt/local/libretext.config.php");
    }
}
