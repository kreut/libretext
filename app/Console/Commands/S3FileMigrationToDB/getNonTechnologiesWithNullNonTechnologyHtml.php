<?php

namespace App\Console\Commands\S3FileMigrationToDB;

use App\Exceptions\Handler;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class getNonTechnologiesWithNullNonTechnologyHtml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:NonTechnologiesWithNullNonTechnologyHtml';

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
     * @param Question $question
     * @return int
     * @throws Exception
     */
    public function handle(Question $question): int
    {
        try {
            if ($count = $question->where('non_technology', 1)->whereNull('non_technology_html')->count()) {
                throw new Exception ("There are $count questions with non_technology = 1 but null non_technology_html");
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        return 0;
    }
}
