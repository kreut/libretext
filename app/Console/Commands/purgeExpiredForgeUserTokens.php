<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class purgeExpiredForgeUserTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purge:expiredForgeUserTokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes forge_user_tokens records that are older than 10 minutes';

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
     * @throws \Throwable
     */
    public function handle()
    {
        try {
            $cutoff = Carbon::now()->subMinutes(10);
            DB::table('forge_user_tokens')
                ->where('created_at', '<', $cutoff)
                ->delete();
            return 1;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            echo $e->getMessage();
        }

        return 0;
    }
}
