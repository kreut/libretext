<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;


class createFakeNames extends Command
{

    /**
     *
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:fakeNames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create fake names for students';

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
        $users = User::all();

        foreach ($users as $user) {
            if ($user->role === 3) {
                $faker = \Faker\Factory::create();
                $user->first_name = $faker->firstName;
                $user->last_name = $faker->lastName;
                $user->email = $faker->email;
                $user->save();
            }
        }
    }
}
