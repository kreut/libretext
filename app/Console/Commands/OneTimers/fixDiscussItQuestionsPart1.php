<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
use App\QuestionMediaUpload;
use App\QuestionRevision;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixDiscussItQuestionsPart1 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:discussItQuestionsPart1';

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
        $question_media_uploads_by_s3_key = [];
        try {
            DB::beginTransaction();
            $question_media_upload_questions = QuestionMediaUpload::get('question_id')->pluck('question_id')->toArray();
            $question_media_upload_questions = array_unique($question_media_upload_questions);
            foreach ($question_media_upload_questions as $question_id) {
                $question = Question::find($question_id);
                if ($question->isDiscussIt()) {
                    $question_media_uploads = QuestionMediaUpload::where('question_id', $question->id)->get();
                    $s3_keys = [];
                    foreach ($question_media_uploads as $question_media_upload) {
                        $s3_keys[] = $question_media_upload->s3_key;
                        $question_media_uploads_by_s3_key[$question_media_upload->s3_key] = $question_media_upload;
                    }
                    $s3_keys = array_unique($s3_keys);
                    $question_revisions = QuestionRevision::where('question_id', $question->id)->get();
                    foreach ($question_revisions as $question_revision) {
                        foreach ($s3_keys as $s3_key) {
                            $questionMediaUpload = QuestionMediaUpload::where('question_id', $question->id)
                                ->where('question_revision_id', $question_revision->id)
                                ->where('s3_key', $s3_key)
                                ->first();
                            if (!$questionMediaUpload) {
                                $questionMediaUpload = $question_media_uploads_by_s3_key[$s3_key]->replicate()
                                    ->fill(['question_revision_id' => $question_revision->id])->toArray();
                                QuestionMediaUpload::create($questionMediaUpload);
                                $this->info($question->id . ' ' . $s3_key . ' ' . $question_revision->id);
                            }
                        }
                    }
                }
            }
            $this->info('Updated the question revisions');
            echo "Done";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
