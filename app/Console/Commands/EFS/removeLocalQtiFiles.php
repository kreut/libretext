<?php

namespace App\Console\Commands\EFS;

use App\Exceptions\Handler;
use DirectoryIterator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class removeLocalQtiFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:localQtiFiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up QTI imports from the local file system';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    function recurseRmdir($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file") && !is_link("$dir/$file")) ? $this->recurseRmdir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        try {
            $efs_dir = '/mnt/local/';
            $is_efs = is_dir($efs_dir);
            $storage_path = $is_efs
                ? $efs_dir
                : Storage::disk('local')->getAdapter()->getPathPrefix();
            $local_dir = "{$storage_path}uploads/qti";
            $dir = new DirectoryIterator($local_dir);
            $time = time();
            foreach ($dir as $fileinfo) {
                if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                    $date_diff = $time - $fileinfo->getMTime();
                    if (round($date_diff / (60 * 60 * 24)) >= 7) {
                        $this->recurseRmdir($fileinfo->getPathName());
                    }
                }
            }
            return 0;
        } catch (Exception $e){
            $h = new Handler(app());
            $h->report($e);
        }
        return 1;
    }
}
