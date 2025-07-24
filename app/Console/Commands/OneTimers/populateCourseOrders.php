<?php

namespace App\Console\Commands\OneTimers;

use App\Course;
use App\CourseOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class populateCourseOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'populate:courseOrders';

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
            DB::beginTransaction();
            $courses = Course::all();
            foreach ($courses as $course) {
                $courseOrder = new CourseOrder();
                $courseOrder->course_id = $course->id;
                $courseOrder->user_id = $course->user_id;
                $courseOrder->order = $course->order;
                $courseOrder->save();
            }
            DB::commit();
            echo "Done!";
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }
}
