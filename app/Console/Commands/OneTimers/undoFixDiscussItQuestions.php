<?php

namespace App\Console\Commands\OneTimers;

use App\Discussion;
use App\QuestionMediaUpload;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class undoFixDiscussItQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'undo:fixDiscussItQuestions';

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
        try {
            DB::beginTransaction();
            $things_to_undo = DB::table('old_discussion_media_uploads')->get();
            foreach ($things_to_undo as $value) {
                Discussion::where('id', $value->discussion_id)
                    ->update(['question_media_upload_id' => $value->question_media_upload_id]);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
