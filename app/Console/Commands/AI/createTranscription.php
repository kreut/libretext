<?php

namespace App\Console\Commands\AI;

use App\Jobs\ProcessTranscribe;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class createTranscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:Transcription {s3_key} {upload_type}';
//art create:Transcription e7008c960dd43ae00b54c20fd3dbdf14.webm discussion_comment
//art create:Transcription 76e35c6b0d457912e52e42c136b015d1.mp3 discussion_comment
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
        //create:Transcription 20ccb3f12dbb3a08cb29220c842d80c0.mp3 discussion_comment
        try {
            $s3_key = $this->argument('s3_key');
            $upload_type = $this->argument('upload_type');
            $job = new ProcessTranscribe($s3_key, $upload_type);
            $job->handle();
        } catch (Exception $e) {
            Log::info($e->getMessage());

        }
        return 0;
    }
}
