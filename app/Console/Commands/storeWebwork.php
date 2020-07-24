<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Question;

class storeWebwork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:Webwork';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gets Webwork questions from the database';

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
     * @return mixed
     */
    public function handle(Question $question)
    {
        $question->storeWebwork();
    }
}
