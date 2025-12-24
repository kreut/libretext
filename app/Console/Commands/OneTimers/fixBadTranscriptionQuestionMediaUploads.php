<?php

namespace App\Console\Commands\OneTimers;

use App\DiscussionComment;
use App\Exceptions\Handler;
use App\QuestionMediaUpload;
use Exception;
use Illuminate\Console\Command;

class fixBadTranscriptionQuestionMediaUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:BadTranscriptionQuestionMediaUploads';

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
     * @throws Exception
     */
    public function handle(): int
    {
        try {
            $question_media_uploads = QuestionMediaUpload::where('status', 'pending')
                ->whereBetween('created_at', [
                    now()->subDays(30)->startOfDay(),
                    now()->subDay()->endOfDay()
                ])
                ->orderBy('created_at', 'desc')
                ->where()
                ->limit(10)
                ->get();
            foreach ($question_media_uploads as $question_media_upload) {
                $this->call('init:ProcessTranscription', [
                    's3_key' => $question_media_upload->s3_key,
                    'upload_type' => 'question_media_upload',
                ]);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
        }
        return 0;
    }
}
