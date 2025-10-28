<?php

namespace App\Console\Commands\OneTimers;

use App\DiscussionComment;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;

class fixDiscussionComment502Errors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:DiscussionComment502Errors';

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
            $discussion_comments = DiscussionComment::where('message', 'Request failed with status: 502 and message: error code: 502')
                ->limit(5)
                ->get();
            foreach ($discussion_comments as $discussion_comment) {
                $this->call('init:ProcessTranscription', [
                    's3_key' => $discussion_comment->file,
                    'upload_type' => 'discussion_comment',
                ]);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
        return 0;
    }
}
