<?php

namespace App\Console\Commands\Notifications;

use App\Exceptions\Handler;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
     * @throws FileNotFoundException|Exception
     */
    public function handle()
    {
        try {
            $date = Carbon::now('America/Los_Angeles')->format('Y-m-d');
            $log_file = "logs/laravel-$date.log";
            if (Storage::disk('s3')->exists($log_file) && time() - Storage::disk('s3')->lastModified($log_file) < 60 * 5) {
                $error_log = Storage::disk('s3')->get("$log_file");
                $pos = strrpos($error_log, "[$date");
                $latest_error = substr($error_log, $pos);
                Telegram::sendMessage([
                    'chat_id' => config('myconfig.telegram_channel_id'),
                    'parse_mode' => 'HTML',
                    'text' => $latest_error
                ]);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        return 0;
    }
}
