<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Query;

class updateQuestionsFromQuery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'query:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates questions by looking at the site updates within a given timeframe';

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
        $query->getQueryUpdates();
    }
}
