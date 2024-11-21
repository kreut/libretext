<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Laravel\Facades\Telegram;

class getSlowDatabaseQueriesSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:slowDatabaseQueriesSummary';

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
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        $last_24_hours = Carbon::now()->subDay()->format('Y-m-d H:i:s');
        try {
            $num_slow_queries = DB::table('execution_times')
                ->where('created_at', '>=', $last_24_hours)
                ->where(function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery->where('execution_time', '>=', 4)
                            ->where('parameters', 'NOT LIKE', '%user_id": 9770%');
                    })->orWhere(function ($subQuery) {
                        $subQuery->where('execution_time', '>', 10)
                            ->where('parameters', 'LIKE', '%user_id": 9770%');
                    });
                })
                ->count();
            $num_queries = DB::table('execution_times')
                ->where('created_at', '>=', $last_24_hours)
                ->where('parameters','NOT LIKE','%user_id": 9770%')
                ->count();
            if ($num_slow_queries) {
                $num_slow_queries_percent = round(100* $num_slow_queries / $num_queries,2);
                Telegram::sendMessage([
                    'chat_id' => config('myconfig.telegram_channel_id'),
                    'parse_mode' => 'HTML',
                    'text' => "$num_slow_queries in the last 24 hours. $num_slow_queries_percent% of total."
                ]);
            } else {
                echo "no slow queries.";
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
