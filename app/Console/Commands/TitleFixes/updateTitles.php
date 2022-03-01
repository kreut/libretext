<?php

namespace App\Console\Commands\TitleFixes;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:titles';

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
            $question_titles = DB::table('question_titles')->get();
            $num_titles = count($question_titles);
            foreach ($question_titles as $key => $value) {
                echo $num_titles - $key . "\r\n";
                if ($value->technology_id) {
                    DB::table('questions')
                        ->where('technology', $value->technology)
                        ->where('technology_id', $value->technology_id)
                        ->update(['title' => $value->title]);

                } else {
                    DB::table('questions')
                        ->where('library', $value->library)
                        ->where('page_id', $value->page_id)
                        ->update(['title' => $value->title]);

                }

            }
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
            return 1;
        }
    }

}
