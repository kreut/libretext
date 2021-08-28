<?php

namespace App\Console\Commands\OneTimers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class convertTextSubmissionsToFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:textSubmissionsToFiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Converts the current text to files and sends to s3';

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
     * @return mixed
     */
    public function handle()
    {
        //get the text from the database
        //convert to a file
        //send to Amazon
        $old_text_submissions = DB::table('old_text_submissions')->get();
        foreach ($old_text_submissions as $value) {
            $filename = md5(uniqid('', true)) . '.html';
            $file_path ="assignments/{$value->assignment_id}/$filename";
            Storage::disk('local')->put($file_path, $value->submission);
            Storage::disk('s3')->put( $file_path, $value->submission, ['StorageClass' => 'STANDARD_IA']);
            DB::table('submission_files')->where('user_id', $value->user_id)
                ->where('assignment_id', $value->assignment_id)
                ->where('question_id', $value->question_id)
                ->where('type', 'text')
                ->update(['submission' => $filename]);
            echo "Assignment: $value->assignment_id, Question: $value->question_id, $file_path : $value->submission";
        }
    }
}
