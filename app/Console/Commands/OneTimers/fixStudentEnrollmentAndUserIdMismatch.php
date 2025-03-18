<?php

namespace App\Console\Commands\OneTimers;

use App\AssignToUser;
use App\Enrollment;
use App\Submission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixStudentEnrollmentAndUserIdMismatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:StudentEnrollmentAndUserIdMismatch';

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
     */
    public function handle()
    {
        try {
            $old_user_id = 124048;
            $new_user_id = 151932;
            DB::beginTransaction();
            $num_updated = Submission::where('user_id', $old_user_id)->update(['user_id' => $new_user_id]);
            echo $num_updated . "\r\n";
            $num_updated = AssignToUser::where('user_id', $old_user_id)->update(['user_id' => $new_user_id]);
            echo $num_updated . "\r\n";

           DB::commit();
        } catch (Exception $e) {
            DB::rollback();
        }
        return 0;
    }
}
