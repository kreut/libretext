<?php

namespace App\Console\Commands;
use App\Jobs\LogFromCRONJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Laravel\Facades\Telegram;

class testVaporCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:VaporCron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'testing vapor CRON';

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
        dispatch(new LogFromCRONJob('some sort of message'));
        $date = Carbon::now('America/Los_Angeles')->format('Y-m-d');
        Telegram::sendMessage([
            'chat_id' => config('myconfig.telegram_channel_id'),
            'parse_mode' => 'HTML',
            'text' =>  $date
        ]);
        $log_file = "logs/laravel-$date.log";
        Telegram::sendMessage([
            'chat_id' => config('myconfig.telegram_channel_id'),
            'parse_mode' => 'HTML',
            'text' =>  $log_file
        ]);
        Telegram::sendMessage([
            'chat_id' => config('myconfig.telegram_channel_id'),
            'parse_mode' => 'HTML',
            'text' =>  storage::disk('s3')->exists($log_file)
        ]);
        Telegram::sendMessage([
            'chat_id' => config('myconfig.telegram_channel_id'),
            'parse_mode' => 'HTML',
            'text' =>  'made it'
        ]);

        Telegram::sendMessage([
            'chat_id' => config('myconfig.telegram_channel_id'),
            'parse_mode' => 'HTML',
            'text' =>  time() - Storage::disk('s3')->lastModified($log_file) < 60 * 5
        ]);

        if (Storage::disk('s3')->exists($log_file) && time() - Storage::disk('s3')->lastModified($log_file) < 60 * 5) {
            $error_log = Storage::disk('s3')->get("$log_file");
            $pos = strrpos($error_log, "[$date");
            $latest_error = substr($error_log, $pos);
            Telegram::sendMessage([
                'chat_id' => config('myconfig.telegram_channel_id'),
                'parse_mode' => 'HTML',
                'text' =>  $latest_error .'incron'
            ]);
        }
    }
}
