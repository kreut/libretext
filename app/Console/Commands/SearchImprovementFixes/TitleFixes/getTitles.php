<?php

namespace App\Console\Commands\SearchImprovementFixes\TitleFixes;

use App\Libretext;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class getTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:Titles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds titles to the question titles tables';

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
    public function handle(Question $question)
    {
        $all_title_info = DB::table('questions')
            ->where('title', null)
            ->where('version', 1)
            ->select('library', 'page_id', 'technology', 'technology_id')
            ->get();
        $question_titles = DB::table('question_titles')->get();
        $question_titles_arr = [];
        foreach ($question_titles as $question_title) {
            $question_titles_arr[$question_title->technology][$question_title->technology_id] = 'set';
        }

        foreach ($all_title_info as $key => $info) {
            if (isset($question_titles_arr[$info->technology][$info->technology_id])) {
                echo "$info->technology $info->technology_id\r\n";
                unset($all_title_info[$key]);
            }
        }
        echo "New\r\n";
        $error_count = 0;
        foreach ($all_title_info as $info) {
            try {
                if ($info->technology === 'h5p') {
                    $h5p_info = $question->getH5PInfo($info->technology_id);
                    $title = $h5p_info['title'];

                } else {

                    $Libretext = new Libretext(['library' => $info->library]);
                    $contents = $Libretext->getPrivatePage('contents', $info->page_id);
                    $attribute = '@title';
                    $title = $contents->$attribute;
                }
                DB::table('question_titles')
                    ->insert(['technology' => $info->technology,
                        'technology_id' => $info->technology_id ?: 0,
                        'title' => $title,
                        'library' => $info->library,
                        'page_id'=> $info->page_id]);
                echo "$info->technology $info->technology_id $title\r\n";
                usleep(250);
            } catch (Exception $e) {
                $error_count++;
                echo $e->getMessage();
                Log::info($info->technology . ' ' . $info->technology_id . ' ' . $e->getMessage());

            }
        }
        echo $error_count;

        return 0;
    }
}
