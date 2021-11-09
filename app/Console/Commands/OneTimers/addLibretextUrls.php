<?php

namespace App\Console\Commands\OneTimers;

use App\Libretext;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class addLibretextUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:LibretextUrls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gets all of the question ids';

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
        ini_set('memory_limit', '750M');

        $data_shop_question_ids = DB::table('data_shops')
            ->get('problem_name')
            ->pluck('problem_name')
            ->toArray();
        $question_ids = array_unique($data_shop_question_ids);
        sort($question_ids);
        $questions = DB::table('questions')
            ->whereIn('id', $question_ids)
            ->select('library', 'page_id', 'id')
            ->get();
        $num_questions = count($questions);
        $start = microtime(true);
        foreach ($questions as $key => $question) {
            try {
                $libretext = new Libretext(['library' => $question->library]);
                $info = $libretext->getPrivatePage('info', $question->page_id);
                $uri = 'uri.ui';
                $url = $info->{$uri};
                echo $num_questions - $key . " $url  \r\n";
                DB::table('data_shops')->where('problem_name', $question->id)->update(['url' => $url]);
                DB::table('questions')->where('id', $question->id)->update(['url' => $url]);
                exit;
            } catch (Exception $e) {
                echo $num_questions - $key . " {$e->getMessage()}  \r\n";
            }
        }
        echo microtime(true) - $start;
        return 0;
    }

}
