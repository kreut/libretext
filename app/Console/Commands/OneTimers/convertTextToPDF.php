<?php

namespace App\Console\Commands\OneTimers;

use App\SubmissionFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spipu\Html2Pdf\Html2Pdf;

class convertTextToPDF extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:textToPdf';

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
        //$pdf = new Fpdi();
        $submissionFile = new SubmissionFile();
        $assignment_id = 347;
        $question_id = 98510;
        DB::beginTransaction();
        $submissionFiles = $submissionFile->where('assignment_id', $assignment_id)
                                            ->where('question_id', $question_id)
                                            ->get();
        foreach ($submissionFiles as $submissionFile) {
            $submission = $submissionFile->submission;
            if (pathinfo($submissionFile->submission, PATHINFO_EXTENSION) === 'html') {
                $submission_text = Storage::disk('s3')->get("assignments/$assignment_id/$submission");
                $html2pdf = new Html2Pdf();
                $html2pdf->writeHTML($submission_text);
                $new_filename = pathinfo($submission, PATHINFO_FILENAME);
                $html2pdf->output(storage_path() . "/app/assignments/$assignment_id/$new_filename.pdf", 'F');
                $contents = file_get_contents(storage_path() . "/app/assignments/$assignment_id/$new_filename.pdf");

                if (!Storage::disk('s3')->exists("/assignments/$assignment_id/$new_filename.pdf")) {
                    Storage::disk('s3')->put("/assignments/$assignment_id/$new_filename.pdf", $contents);
                    echo "$new_filename.pdf added \r\n";
                } else {
                    echo "$new_filename.pdf exists \r\n";

                }
                $submissionFile->type = 'q';
                $submissionFile->submission = "$new_filename.pdf";
                $submissionFile->original_filename = "submission.pdf";
                $submissionFile->save();
            }
        }
        DB::commit();
    }
}
