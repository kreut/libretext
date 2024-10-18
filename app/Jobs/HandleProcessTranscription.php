<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use App\QuestionMediaUpload;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleProcessTranscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $filename;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    public function handle()
    {
        try {
            $questionMediaUpload = new QuestionMediaUpload();
            $questionMediaUpload->processTranscribe($this->filename);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
