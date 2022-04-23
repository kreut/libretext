<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class addTypeIdToH5PQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:TypeIdToH5PQuestions';

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
        $time = microtime(true);
        $h5p_questions = DB::table('questions')->where('technology', 'h5p')->get();
        $already_done_questions = DB::table('h5p_type_ids')->get('question_id')->pluck('question_id')->toArray();
        foreach ($h5p_questions as $h5p_question) {
            if (in_array($h5p_question->id, $already_done_questions)) {
                continue;
            }
            $already_done_questions[] = $h5p_question->id;
            $endpoint = "https://studio.libretexts.org/api/h5p/{$h5p_question->technology_id}";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $output = curl_exec($ch);
            $output = json_decode($output);
            if (!is_array($output)) {
                echo $endpoint . " not an array\r\n";
                continue;
            }
            if (!isset($output[0])) {
                echo $endpoint . " no offset 0\r\n";
                continue;
            }

            DB::table('h5p_type_ids')->insert(['question_id' =>  $h5p_question->id,
                'type_id' => $output[0]->type_id,
                'technology_id' => $h5p_question->technology_id]);

        }
        echo microtime(true) - $time;
        return 0;
    }
}
