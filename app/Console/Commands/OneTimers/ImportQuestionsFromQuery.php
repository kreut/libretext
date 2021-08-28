<?php

namespace App\Console\Commands\OneTimers;

use Illuminate\Console\Command;
use App\Libretext;

class ImportQuestionsFromQuery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'query:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the questions from query';

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
       $libretext = new Libretext(['library' => 'query']);
       $libretext->import();
    }
}
