<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use App\Question;
use Exception;
use Illuminate\Console\Command;

class getNonAdaptQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:nonAdaptQuestions';

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
        try {
            if (Question::where('library', '<>', 'adapt')->first()) {
                throw new Exception("There are non-adapt questions in the database.");
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        echo "No non-adapt questions.";
        return 0;
    }
}
