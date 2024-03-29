<?php

namespace App\Console\Commands\EFS;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class copyEFSToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'copy:EFSToS3 {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy file contents from EFS to S3';

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
     * @throws Exception
     */
    public function handle()
    {
        $file= $this->argument('file');
        $dir = '/mnt/local';
        try {

            $file_contents = file_get_contents($dir . '/' . $file);
            Storage::disk('s3')->put('efs_file_contents.txt', $file_contents);
            echo $file_contents;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        return 0;
    }
}
