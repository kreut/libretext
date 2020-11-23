<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RefactorNonTechnology extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nontechnology:refactor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takes current nontechnology questions and refactors the js, css, and sends to s3';

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
        //get all nontechnology files
        //Pull out mathjax
        //add new mathjax config script
        //add global css script
        //add global js script
        //send to query on S3

        $files = Storage::disk('public')->allFiles();
        foreach ($files as $file) {
            $contents = Storage::disk('public')->get($file);
            if (strpos($file, '.html') !== false && strpos($contents, 'mathjax') !== false) {
                $contents = '<link rel="stylesheet" href="' . env('APP_URL') . '/public/assets/css/query.css">' . $contents;
                $contents = preg_replace('#<script type="text/x-mathjax-config">(.*?)</script>#is', '<script type="text/javascript" src="' . env('APP_URL') . '/public/assets/js/mathjax.js"', $contents);
                Storage::disk('s3')->put("query/$file", $contents, ['StorageClass' => 'STANDARD_IA']);
            }
        }
    }
}
