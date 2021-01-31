<?php

namespace App\Console\Commands;

use App\Assignment;
use App\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class setInitialCourseAssignmentOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courseAssignments:setInitialOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the orders of the assignments.';

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
        $courses = Course::all();
        DB::beginTransaction();
        foreach ($courses as $course) {
            $assignments = $course->assignments;
            $assignment = new Assignment();
            if ($assignments) {
                $ordered_assignments = [];
                foreach ($assignments as $assignment) {
                    $ordered_assignments[] = $assignment->id;
                }
                $assignment->orderAssignments($ordered_assignments, $course);
            }
        }
        DB::commit();
    }
}
