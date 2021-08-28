<?php

namespace App\Console\Commands\OneTimers;

use App\User;
use Illuminate\Console\Command;

class createAnonymousUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:anonymousUser';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the anonymous user in the system';

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
        $user = new User();
        $user->last_name = '';
        $user->first_name = 'Anonymous';
        $user->role = 3;
        $user->email = 'anonymous';
        $user->time_zone = 'America/Los_Angeles';
        $user->password = bcrypt('anonymous');
        $user->save();
    }
}
