<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class testTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:telegram';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifies that telegram is functioning properly';

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
        Telegram::sendMessage([
            'chat_id' => config('myconfig.telegram_channel_id'),
            'parse_mode' => 'HTML',
            'text' => 'Test message'
        ]);
        return 0;
    }
}
