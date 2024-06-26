<?php

namespace App\Console\Commands\H5P;

use Illuminate\Console\Command;
use App\Question;

class storeH5P extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:H5P';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stores H5P questions in the database';

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
        $question->storeH5P();
    }
}
