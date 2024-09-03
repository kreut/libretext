<?php

namespace App\Console\Commands\AI;

use App\Jobs\ProcessTranscribe;
use Exception;
use Illuminate\Console\Command;

class createTranscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:Transcription {s3_key} {upload_type}';

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
     * @return int
     */
    public function handle()
    {
        //create:Transcription ee97f036e5d70ea252e4e46ff7811304.webm discussion_comment
        try {
            $s3_key = $this->argument('s3_key');
            $upload_type = $this->argument('upload_type');
            $job = new ProcessTranscribe($s3_key, $upload_type);
            $job->handle();
        } catch (Exception $e) {
            echo $e->getMessage();

        }
        return 0;
    }
}
