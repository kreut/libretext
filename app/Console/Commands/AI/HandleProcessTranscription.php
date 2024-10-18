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
        //staging:  init:ProcessTranscription 55b4b318d21e1e45a95b7434cb635213.webm discussion_comment ---big one
        //staging:  init:ProcessTranscription b451db76d66056595871fd6bafcd826f.webm discussion_comment ---REALLY big one
        //staging: init:ProcessTranscription 254e9e09bc5226bf4e4e52862df14fbc.webm discussion_comment
        //staging: smaller webp --- init:ProcessTranscription 08f07e2f7061198c3a22dfe2807a3e53.webm discussion_comment
        //staging: smaller mp4 --- init:ProcessTranscription c9eca2e0d6f1e127c0792ef8c6db6061.mp4 discussion_comment
        //staging init:HandleProcessTranscription 9099ebc40838fa67b6f6ba9d1fc7c84a.mp3 discussion_comment

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
