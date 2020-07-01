<?php

use Illuminate\Database\Seeder;
use App\Role;
use App\User;

class RoleSeeder extends Seeder
{
    /**
     * Make all users students except for the first one
     *
     * @return void
     */
    public function run()
    {

        $users = User::get();
        foreach ($users as $user) {
            Role::create([
                'user_id' => $user->id,
                'name' => ($user->id === 1) ? 'instructor' : 'student'
            ]);
        }
    }
}
