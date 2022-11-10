<?php

namespace App\Console\Commands\NoRole;

use App\Exceptions\Handler;
use App\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use function app;
use function now;

class getUsersWithNoRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:usersWithZeroRole';

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
    public function handle()
    {

        try {
            $users = User::where('role', 0)->get();
            if ($users->isNotEmpty()) {
                foreach ($users as $user) {
                    $user_with_no_role = DB::table('users_with_no_role')
                        ->where('user_id', $user->id)
                        ->first();
                    if (!$user_with_no_role) {
                        DB::table('users_with_no_role')->insert([
                            'user_id' => $user->id,
                            'first_name' => $user->first_name,
                            'last_name'=> $user->last_name,
                            'email'=> $user->email,
                            'student_id' => $user->student_id,
                            'created_at' => now(),
                            'updated_at' => now()]);
                    }
                }
            }
            $users_with_no_role = DB::table('users_with_no_role')
                ->join('users', 'users_with_no_role.user_id', '=', 'users.id')
                ->where('users_with_no_role.status', 0)
                ->get();
            if ($users_with_no_role->isNotEmpty()) {
                $emails = '';
                foreach ($users_with_no_role as $user_with_no_role) {
                    $emails .= "$user_with_no_role->email\r\n";
                    DB::table('users_with_no_role')
                        ->where('user_id', $user_with_no_role->user_id)
                        ->update(['status' => 1]);
                }
                throw new Exception ("Users with no role: $emails");
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        return 0;
    }
}
