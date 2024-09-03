<?php

namespace App\Console\Commands\AI;

use App\DiscussionComment;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;

class fixMaxErrorIssue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:maxErrorIssue';

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
     * @param DiscussionComment $discussionComment
     * @return int
     * @throws Exception
     */
    public function handle(DiscussionComment $discussionComment)
    {
        $discussion_comments = $discussionComment->where('message', 'LIKE', '%Maximum content size%')
            ->limit(5)
            ->get();
        foreach ($discussion_comments as $discussion_comment) {
            try {
                $this->call('create:Transcription',
                    ['s3_key' => $discussion_comment->file,
                        'upload_type' => 'discussion_comment'
                    ]);
            } catch (Exception $e){
                $h = new Handler(app());
                $h->report($e);
            }
        }

        return 0;
    }
}
