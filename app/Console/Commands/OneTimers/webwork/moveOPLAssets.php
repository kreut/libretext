<?php

namespace App\Console\Commands\OneTimers\webwork;

use App\Question;
use Exception;
use Illuminate\Console\Command;

class moveOPLAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'move:OPLAssets';

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
            $opl_questions = Question::where('technology', 'webwork')
                ->where('technology_id', 'NOT LIKE', 'private%')
                ->whereNULL('webwork_code')
                ->get();
            $top_levels = [];
            foreach ($opl_questions as $question) {
                $dirs = explode('/', dirname($question->technology_id));
                $top_level = $dirs[0];
                if (!in_array($top_level, $top_levels)) {
                    $top_levels[] = $top_level;
                    echo $top_level . "\r\n";
                }

            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
