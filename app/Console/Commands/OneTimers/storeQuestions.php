<?php

namespace App\Console\Commands\OneTimers;

use Illuminate\Console\Command;
use App\Question;
class storeQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:questions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store 3rd party questions locally';

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
     * @param Question $question
     */
    public function handle(Question $question)
    {
        $question->store();
    }
}

