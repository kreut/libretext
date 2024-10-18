<?php

namespace App\Console\Commands\AI;

use App\Exceptions\Handler;
use App\Jobs\InitProcessTranscribe;
use App\Question;
use App\QuestionMediaUpload;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HandleProcessTranscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'handle:ProcessTranscription {filename}';
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
     * @param QuestionMediaUpload $questionMediaUpload
     * @return int
     * @throws Exception
     */
    public function handle(QuestionMediaUpload $questionMediaUpload): int
    {

        set_time_limit(600);
        //init:ProcessTranscription 9099ebc40838fa67b6f6ba9d1fc7c84a.mp3 discussion_comment
        //staging:  init:ProcessTranscription e01072bf5ee468aefcd34bc0a39b2d20.mp3 discussion_comment
        //  handle:ProcessTranscription 91fe46a21ab6014f660c6427d0778890.mp3

        try {
            $filename = $this->argument('filename');
            $questionMediaUpload->processTranscribe( $filename);

            return 0;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
    }
}
