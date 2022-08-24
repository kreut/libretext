<?php

namespace App\Console\Commands\H5P;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
use Exception;
use Illuminate\Console\Command;

class fixNullH5pTypeAndSourceUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:NullH5pTypeAndSourceUrl';

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
    public function handle()
    {
        $h5p_questions = Question::where('technology', 'h5p')
            ->whereNull('h5p_type')
            ->select('id', 'technology_id')
            ->get();
        $num_questions = count($h5p_questions);
echo "Start\r\n";
        foreach ($h5p_questions as $key => $question) {
            try {
                $h5p_object = Helper::h5pApi($question->technology_id);
                if (isset($h5p_object[0]['type'])) {
                    $type = $h5p_object[0]['type'];
                    $source_url = !isset($h5p_object[0]['h5p_source']) || !$h5p_object[0]['h5p_source'] ? "https://studio.libretexts.org/h5p/$question->technology_id" : $h5p_object[0]['h5p_source'];
                    $question->h5p_type = $type;
                    $question->source_url = $source_url;
                    $question->save();
                    echo $num_questions - $key . " $question->id $type $source_url\r\n";
                } else {
                    echo $num_questions - $key . " $question->id No type exists\r\n";
                }
            } catch (Exception $e) {
                echo $num_questions - $key . " " . $e->getMessage() . "\r\n";
                $h = new Handler(app());
                $h->report($e);
            }
        }
        echo "Done";
        return 0;

    }
}
