<?php

namespace App\Console\Commands\AI;

use App\Exceptions\Handler;
use App\Jobs\InitProcessTranscribe;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class initProcessTranscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:ProcessTranscription {s3_key} {upload_type}';
//art init:ProcessTranscription e7008c960dd43ae00b54c20fd3dbdf14.webm discussion_comment
//art init:ProcessTranscription c64975e915f6874556d45d2df144c0da.webm discussion_comment
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
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {

        //staging: smaller mp4 --- init:ProcessTranscription c9eca2e0d6f1e127c0792ef8c6db6061.mp4 discussion_comment
        //staging init:ProcessTranscription cd4fa71c2d8fb7e4216ddffc3ed25437.webm discussion_comment
       // init:ProcessTranscription 91fe46a21ab6014f660c6427d0778890.mp3 question_media_upload
        try {
            $s3_key = $this->argument('s3_key');
            $upload_type = $this->argument('upload_type');
            $job = new initProcessTranscribe($s3_key, $upload_type);
            $job->handle();
            return 0;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
    }
}
