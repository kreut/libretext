<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixSubmissionFileRoundingForLarry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:SubmissionFileRoundingForLarry';

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
           DB::table('submission_files')->where('assignment_id', 3443)
               ->update(['score' => .0633]);
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }
}
