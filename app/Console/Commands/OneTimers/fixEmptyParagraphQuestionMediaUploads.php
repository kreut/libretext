<?php

namespace App\Console\Commands\OneTimers;

use App\QuestionMediaUpload;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class fixEmptyParagraphQuestionMediaUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:emptyParagraphQuestionMediaUploads';

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
        $questionMediaUpload = new QuestionMediaUpload();
        try {
            $question_media_uploads_to_fix = $questionMediaUpload
                ->where('text', 'LIKE', '%<p>&nbsp;</p>%')
                ->get();
            DB::beginTransaction();
            foreach ($question_media_uploads_to_fix as $question_media_upload_to_fix) {
                echo $question_media_upload_to_fix->id . "\r\n";
                $data = ['question_media_upload_id' => $question_media_upload_to_fix->id,
                    'text' => $question_media_upload_to_fix->text];
                DB::table('empty_paragraph_question_media_upload_fixes')->insert($data);
                $question_media_upload_to_fix->text = str_replace('<p>&nbsp;</p>', '', $question_media_upload_to_fix->text);
                $question_media_upload_to_fix->save();
                Storage::disk('s3')->put("{$questionMediaUpload->getDir()}/$question_media_upload_to_fix->s3_key", $question_media_upload_to_fix->text);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
