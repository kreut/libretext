<?php

namespace App\Console\Commands\Cleanup;

use App\Exceptions\Handler;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class removePendingCourseInvitationsFromClosedCourses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:pendingCourseInvitationsFromClosedCourses';

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
     * @throws \Exception
     */
    public function handle()
    {
        try {
            $course_ids = DB::table('courses')
                ->where('end_date', '<', Carbon::now())
                ->get()
                ->pluck('id')
                ->toArray();
            DB::table('pending_course_invitations')
                ->whereIn('course_id', $course_ids)
                ->delete();
            return 0;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            echo $e->getMessage();
            return 1;
        }
    }
}
