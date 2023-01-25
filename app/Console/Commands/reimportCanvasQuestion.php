<?php

namespace App\Console\Commands;

use App\QtiImport;
use App\QtiJob;
use App\Question;
use DOMDocument;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tests\Feature\QTI\QtiImportTest;

class reimportCanvasQuestion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reimport:CanvasQuestion {question_ids}';

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
        $question_ids = $this->argument('question_ids');
        $question_ids = explode(',', $question_ids);

        try {
            DB::beginTransaction();
            foreach ($question_ids as $question_id) {
                echo "Processing $question_id";
                $qtiImport = QtiImport::where('question_id', $question_id)
                    ->latest()
                    ->first();

                if (!$qtiImport) {
                    throw new Exception ("No qti import with question ID $question_id.");
                }
                $xml = $qtiImport->cleanUpXml($qtiImport->xml);
                $xml_array = json_decode(json_encode($xml), true);
                $qti_job = QtiJob::find($qtiImport->qti_job_id);
                $domDocument = new DOMDocument();
                $question = Question::find($question_id);

                $canvas_import_info = $qtiImport->processCanvasImport($xml_array,
                    $qtiImport,
                    $qti_job,
                    $question->question_editor_user_id,
                    $domDocument,
                    $question);
                $xml_array = $canvas_import_info['xml_array'];
                $non_technology_html = $canvas_import_info['non_technology_html'] ?: '';

                if ($canvas_import_info['message']) {
                    dd($canvas_import_info['message']);
                }
                $question_type = $canvas_import_info['question_type'];
                $qtiImport->updateByQuestionType($question, $question_type, $non_technology_html, $xml_array);
                $question->save();
                echo "...re-saved\r\n";
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
