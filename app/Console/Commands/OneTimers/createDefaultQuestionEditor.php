<?php

namespace App\Console\Commands\OneTimers;

use App\User;
use Illuminate\Console\Command;

class createDefaultQuestionEditor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:defaultQuestionEditor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the default question editor user in the system';

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
     * @return void
     */
    public function handle()
    {
        $user = new User();
        $user->last_name = '';
        $user->first_name = 'Default Question Editor';
        $user->role = 5;
        $user->email = 'Default Question Editor has no email';
        $user->time_zone = 'America/Los_Angeles';
        $user->password = bcrypt(substr(sha1(mt_rand()), 17, 12));
        $user->save();
    }
}
