<?php

namespace App\Console\Commands\ckeditor;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixEmptyParagraphNonTechnologyHtml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:emptyParagraphNonTechnologyHtml';

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
     * @param Question $question
     * @return int
     */
    public function handle(Question $question): int
    {
        try {
            $questions_to_fix = $question
                ->where('non_technology_html', 'LIKE', '%<p>&nbsp;</p>%')
                ->get();
            DB::beginTransaction();
            foreach ($questions_to_fix as $question_to_fix) {
                echo $question_to_fix->id . "\r\n";
                $data = ['question_id' => $question_to_fix->id,
                    'non_technology_html' => $question_to_fix->non_technology_html];
                DB::table('empty_paragraph_non_technology_html_fixes')->insert($data);
                $question_to_fix->non_technology_html = str_replace('<p>&nbsp;</p>', '', $question_to_fix->non_technology_html);
                $question_to_fix->save();
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
