<?php

namespace App\Console\Commands\OneTimers;

use App\Jobs\GenerateFlashcardTTS;
use App\Question;
use App\QuestionRevision;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class updateSpanishTTS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:SpanishTTS';

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
            $questions = Question::where('question_editor_user_id', 7420)
                ->where('qti_json_type', 'flashcard')
                ->get();

            $csvPath = storage_path('app/tts_output_' . now()->format('Y-m-d_His') . '.csv');
            $handle = fopen($csvPath, 'w');
            fputcsv($handle, ['term', 'url']);

            foreach ($questions as $question) {
                $question_revisions = QuestionRevision::where('question_id', $question->id)->get();
                foreach ($question_revisions as $question_revision) {
                    $qti_json = json_decode($question_revision->qti_json);
                    if ($qti_json->card->term && (!property_exists($qti_json->card, 'addedLanguageContext') || !$qti_json->card->addedLanguageContext)) {
                        $job = new GenerateFlashcardTTS($question->id, $question_revision->id);
                        $s3Key = $job->processForSide('front', $qti_json->card->term);
                        $url = Storage::disk('s3')->temporaryUrl($s3Key, now()->addDays(7));

                        fputcsv($handle, [$qti_json->card->term, $url]);

                        $this->line($qti_json->card->term);
                        //$this->line($url);
                    }
                }
            }

            fclose($handle);
            $this->info("CSV saved to: {$csvPath}");
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }
}
