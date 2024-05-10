<?php

namespace App\Console\Commands\SearchImprovementFixes\TitleFixes;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class pruneTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prune:Titles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove legitimate titles from the questions_better_titles_page';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {


        //In case I need to reload the data
         $handle = fopen("/Users/franciscaparedes/Downloads/question_better_titles_final.csv", 'r');
          while (($row = fgetcsv($handle, 1000)) !== false) {
              $data = ['id' => $row[0],
                  'url' => $row[1],
                  'title' => $row[2],
                  'created_at' => Carbon::parse($row[3]),
                  'updated_at' => Carbon::parse($row[4])];
              DB::table('question_better_titles')->insert($data);
          }

          fclose($handle);
          dd('test');

        /* DB::table('question_better_titles')
             ->where('title', 'NOT REGEXP', '^[0-9]+$')
             ->where('title', 'NOT LIKE', '%.pg')
             ->delete();*/
        $question_better_titles = DB::table('question_better_titles')
            ->select('id', 'url', 'title')
            ->get();
        foreach ($question_better_titles as $question_better_title) {
            /* $page_id = DB::table('questions')
                 ->where('id',$question_better_title->id)
                 ->select('page_id')
                 ->first()
                 ->page_id;
             $url = $question_better_title->url;
             $url = str_replace('Community_Gallery/IMathAS_Assessments/', '', $url);
             $url = str_replace('Community_Gallery/WeBWorK_Assessments/', '', $url);
             $url = str_replace('https://query.libretexts.org/', '', $url);
             $url = str_replace('Textbook_Specific/', '', $url);
             $url = str_replace('Contributed_libraries/', '', $url);
             $url = str_replace('_', ' ', $url);
             */
            //$url = $question_better_title->url;
            //$url = str_replace('Contributed Libraries/', '', $url);
            // $url = str_replace('Hasselberg Steve/', '', $url);
            // $url = str_replace('Examples', '', $url);
            //$url = strpos($url, '/') !== false ? substr($url, 0, strrpos( $url, '/')) : $url;
            // $filename = pathinfo($url)['filename'];
            /* $starts_with_number = is_numeric($question_better_title->title[0]);
             if ($starts_with_number) {
                 $title_arr = explode('-', $question_better_title->title);
                 $chapter = pathinfo($question_better_title->url)['filename'];
                 $title = $chapter . '-' . $title_arr[1];
                 echo pathinfo($question_better_title->url)['dirname'] . '       ' . $title . "\r\n";
                 DB::table('question_better_titles')
                     ->where('id', $question_better_title->id)
                     ->update(['url' => pathinfo($question_better_title->url)['dirname'],
                         'title' => $title]);
             }*/
            /* if ($question_better_title->url === 'Contributed Libraries' || $question_better_title->url === 'StitzZeager') {
                 DB::table('question_better_titles')
                     ->where('id', $question_better_title->id)
                     ->update(['url' => '']);
             }*/
           // $url = str_replace('Physics Library', 'Physics', $question_better_title->url);
            $url = trim(str_replace('Lippman', '', $question_better_title->url));
            if ( $question_better_title->url === 'Lippmann') {
                DB::table('question_better_titles')
                    ->where('id', $question_better_title->id)
                    ->update(['url' => $url]);
            }
        }
    }
}
