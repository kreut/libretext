<?php

namespace App\Console\Commands\ckeditor;

use App\Exceptions\Handler;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class getEmptyParagraphNonTechnologyHtml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:emptyParagraphNonTechnologyHtml';

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
     * @throws Exception
     */
    public function handle(): int
    {
        try {
            $question_ids = [];
            $bad_questions = DB::table('questions')
                ->where('non_technology_html', 'LIKE', '%<p>&nbsp;</p>%')
                ->get();
            if ($bad_questions) {
                $question_ids = $bad_questions->pluck('id')->toArray();
            }
            if ($question_ids) {
                $formatted_question_ids = implode(", ", $question_ids);
                $questions_to_fix = Question::whereIn('id',$question_ids)->get();
                foreach ($questions_to_fix as $question_to_fix) {
                    $question_to_fix->non_technology_html = str_replace('<p>&nbsp;</p>', '', $question_to_fix->non_technology_html);
                    $question_to_fix->save();
                }
                throw new Exception("$formatted_question_ids had the extra empty paragraph tags.");
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
