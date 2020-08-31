<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Query;

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
       $query = new Query();
       $query->import();
    }
}
