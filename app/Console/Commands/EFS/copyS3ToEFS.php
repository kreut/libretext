<?php

namespace App\Console\Commands\EFS;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class copyS3ToEFS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'copy:S3ToEFS {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy file contents from S3 to EFS';

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
        $file = $this->argument('file');

        $dir = '/mnt/local';
        try {
            $contents = Storage::disk('s3')->get($file);
            $file_dir = dirname($file);
            if ($file_dir && !is_dir($file_dir)) {
                mkdir("$dir/$file_dir");
            }
            $filename = $dir . '/' . $file;
            echo "\r\n$filename\r\n";
            file_put_contents($filename, $contents);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        echo "File put to EFS.";
        return 0;
    }
}
