<?php

namespace App\Console\Commands\ckeditor;

use App\Exceptions\Handler;
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
                throw new Exception("$formatted_question_ids have the extra empty paragraph tags.");
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
