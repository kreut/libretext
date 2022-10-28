<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class resetTimeInReviews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:timeInReviews';

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
            DB::beginTransaction();
            DB::table('data_shops')
                ->whereNotNull('review_time_start')
                ->delete();
            DB::table('review_histories')->truncate();
            DB::commit();
        } catch (Exception $e) {
            echo $e->getMessage();
            DB::rollback();
        }
        return 0;
    }
}
