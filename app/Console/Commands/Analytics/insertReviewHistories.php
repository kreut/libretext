<?php

namespace App\Console\Commands\Analytics;

use App\Assignment;
use App\DataShop;
use App\Exceptions\Handler;
use App\ReviewHistory;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\VarDumper\Cloner\Data;
use Telegram\Bot\Laravel\Facades\Telegram;

class insertReviewHistories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:reviewHistories';

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
     * @throws Exception
     */
    public function handle(DataShop $dataShop)
    {
        try {
            $start_time = microtime(true);
            $review_histories = DB::table('review_histories')
                ->join('users', 'review_histories.user_id', '=', 'users.id')
                ->select('users.email', 'review_histories.*')
                ->where('review_histories.updated_at', '>=', Carbon::now()->subHours(12)->toDateTimeString())
                ->where('review_histories.updated_at', '<=', Carbon::now()->subHours(0)->toDateTimeString())
                ->where('saved_to_data_shops', 0)
                ->get();
            foreach ($review_histories as $review_history) {
                $dataShop = new DataShop();
                $assignment = Assignment::find($review_history->assignment_id);
                $user = User::find($review_history->user_id);
                Auth::login($user);
                $assignment_question = DB::table('assignment_question')
                    ->where('assignment_id', $review_history->assignment_id)
                    ->where('question_id', $review_history->question_id)
                    ->first();
                $dataShop->store('time_to_review', $review_history, $assignment, $assignment_question);
                DB::table('review_histories')
                    ->where(['id' => $review_history->id])
                    ->update(['saved_to_data_shops' => 1, 'updated_at' => now()]);
            }
            $total_time = microtime(true) - $start_time;
            if ($total_time > 10) {
                Telegram::sendMessage([
                    'chat_id' => config('myconfig.telegram_channel_id'),
                    'parse_mode' => 'HTML',
                    'text' => "Updating the review histories to data_shops took $total_time seconds."
                ]);


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
