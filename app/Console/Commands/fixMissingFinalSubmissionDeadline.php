<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixMissingFinalSubmissionDeadline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:missingFinalSubmissionDeadline {course_id}';

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
    public function handle(): int
    {
        $course_id = $this->argument('course_id');
        try {
            DB::beginTransaction();
            $assignment_ids = DB::table('assignments')
                ->select('id')
                ->where('course_id', $course_id)
                ->where('late_policy', '<>', 'not accepted')
                ->get()
                ->pluck('id')
                ->toArray();
            echo "Number to fix: " . count($assignment_ids) . "\r\n";
            foreach ($assignment_ids as $assignment_id) {
                DB::table('assign_to_timings')
                    ->where('assignment_id', $assignment_id)
                    ->update(['final_submission_deadline' => now(), 'updated_at' => now()]);
                echo "$assignment_id\r\n";
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
