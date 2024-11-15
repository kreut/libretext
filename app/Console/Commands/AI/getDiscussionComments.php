<?php

namespace App\Console\Commands\AI;

use App\DiscussionComment;
use App\QuestionMediaUpload;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class getDiscussionComments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:discussionComments {assignment_id}';

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
    public function handle(QuestionMediaUpload $questionMediaUpload)
    {
        try {
            $comments = DiscussionComment::whereIn('discussion_id', function ($query) {
                $assignment_id = $this->argument('assignment_id');
                $query->select('id')
                    ->from('discussions')
                    ->where('media_upload_id', $assignment_id);
            })->get();
            foreach ($comments as $key => $comment) {
                $s3_key = "{$questionMediaUpload->getDir()}/$comment->file";
                if ($comment->file !== '543f9acdd8cbc3bec9a682c41e588383.webm' && !Storage::disk('s3')->exists($s3_key)) {
                    if (Storage::disk('production_s3')->exists($s3_key)) {
                        echo $key + 1 . " $comment->file retrieving ";
                        $fileContent = Storage::disk('production_s3')->get($s3_key);
                        echo "putting";
                        Storage::disk('s3')->put($s3_key, $fileContent);
                        echo " done\r\n";

                    }
                    $vtt_file = $questionMediaUpload->getVttFileNameFromS3Key($comment->file);
                    $s3_key = "{$questionMediaUpload->getDir()}/$vtt_file";
                    if (Storage::disk('production_s3')->exists($s3_key)) {
                        $fileContent = Storage::disk('production_s3')->get($s3_key);
                        Storage::disk('s3')->put($s3_key, $fileContent);
                        echo $s3_key;
                    }
                }
                echo $s3_key . "\r\n";
            }
        } catch (Exception $e) {
            echo $e->getMessage();

        }
        return 0;
    }
}
