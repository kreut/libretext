<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class queryToLibretextConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queryTo:LibreTextConfig';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $files = \Storage::disk('local')->files('query');
        foreach ($files as $file) {
            $contents = \Storage::disk('local')->get("$file");
            $contents = str_replace("require_once(__DIR__ . '/../query.config.php')", "require_once(__DIR__ . '/../libretext.config.php')", $contents);
            \Storage::disk('local')->put("$file", $contents);
            \Storage::disk('s3')->put("$file", $contents);
            echo "$file\r\n";
        }
        dd("done");
    }
}
