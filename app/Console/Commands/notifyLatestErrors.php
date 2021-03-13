<?php

namespace App\Console\Commands;

use App\Mail\EmailError;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Telegram\Bot\Laravel\Facades\Telegram;

;

class notifyLatestErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:LatestErrors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notifies me of the latest errors';

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
    public function handle()
    {
        $date = Carbon::now()->format('Y-m-d');
        $log_file = storage_path() . "/logs/laravel-$date.log";
        if (file_exists($log_file) && time() - filemtime($log_file) < 60 * 5) {
            $error_log = file_get_contents(storage_path() . "/logs/laravel-$date.log");
            $pos = strrpos($error_log, "[$date");
            $latest_error = substr($error_log, $pos);
            Telegram::sendMessage([
                'chat_id' => config('myconfig.telegram_channel_id'),
                'parse_mode' => 'HTML',
                'text' =>  $latest_error
            ]);
        }
    }
}
