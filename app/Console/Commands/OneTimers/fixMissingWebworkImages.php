<?php

namespace App\Console\Commands\OneTimers;

use App\Webwork;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixMissingWebworkImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:missingWebworkImages';

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
     * @throws \Exception
     */
    public function handle(Webwork $webwork)
    {

        $question_ids = DB::table('webwork_attachments')
            ->select('question_id')
            ->groupBy('question_id')
            ->havingRaw('COUNT(question_id) > 1')
            ->get()
            ->pluck('question_id')
            ->toArray();
        $webwork_attachments = DB::table('webwork_attachments')
            ->whereIn('question_id', $question_ids)
            ->select('question_id', 'question_revision_id', 'filename')
            ->get();
        $webwork_attachments_by_question_id = [];
        foreach ($webwork_attachments as $webwork_attachment) {
            $webwork_attachments_by_question_id[$webwork_attachment->question_id] = $webwork_attachment->filename;
        }
        foreach ($webwork_attachments_by_question_id as $question_id => $filename) {
            if (!file_exists("/Users/franciscaparedes/Downloads/webwork/$question_id/$filename")) {
                // echo "$filename does not exist for $question_id.\r\n";
            } else {
                foreach ($webwork_attachments as $webwork_attachment) {
                    if ($webwork_attachment->question_id === $question_id && $webwork_attachment->question_revision_id) {
                        $dir = "/Users/franciscaparedes/Downloads/fixed_webwork_images/$question_id-$webwork_attachment->question_revision_id";
                        if (!file_exists($dir)) {
                            mkdir($dir);
                        }
                        $webwork_dir = "private/ww_files/$question_id-$webwork_attachment->question_revision_id";
                        $files = $webwork->listDir($webwork_dir); //must remove the local/production safeguard

                        $image_exists = false;
                        foreach ($files as $file => $path) {
                            if ($file === $webwork_attachment->filename) {
                                $image_exists = true;
                            }
                        }
                        $local_path = "$dir/$filename";
                        if (!file_exists($local_path)) {
                            copy("/Users/franciscaparedes/Downloads/webwork/$question_id/$filename", $local_path);
                        }
                        if (!$image_exists) {
                            echo "$webwork_attachment->filename does not exist in $question_id-$webwork_attachment->question_revision_id\r\n";
                            $webwork->putLocalAttachmentToLiveServer($filename, $local_path, "$question_id-$webwork_attachment->question_revision_id");
                        }


                    }
                }
            }
        }
        return 0;
    }
}
