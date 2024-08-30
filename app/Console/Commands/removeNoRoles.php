<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class removeNoRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:NoRoles';

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
            DB::beginTransaction();
            $fiveMinutesAgo = Carbon::now()->subMinutes(5);
            $tenMinutesAgo = Carbon::now()->subMinutes(10);

            $users_with_no_role = User::where('role', 0)
                ->whereBetween('created_at', [$tenMinutesAgo, $fiveMinutesAgo])
                ->get();
            foreach ($users_with_no_role as $user_with_no_role) {
                echo $user_with_no_role->id . ' ';
                DB::table('users_with_no_role')->where('user_id', $user_with_no_role->id)->delete();
                $user_with_no_role->delete();
            }
            DB::commit();
            return 0;
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
    }
}
