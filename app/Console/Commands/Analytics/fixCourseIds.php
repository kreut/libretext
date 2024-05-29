<?php

namespace App\Console\Commands\Analytics;

use App\DataShop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class fixCourseIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:CourseIds';

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
    public function handle(DataShop $dataShop)
    {
        $cell_data = Cache::get('cell_data');
        $data_shops_enrollments = DB::table('data_shops_enrollments')->get();
        foreach ($data_shops_enrollments as $value) {
                $data_shops_complete = DB::table('data_shops_complete')
                    ->where('course_name', $value->course_name)
                    ->where('school', $value->school_name)
                    ->where('instructor_name', $value->instructor_name)
                    ->select('course_id')
                    ->first();
                if ($data_shops_complete) {
                    echo $value->course_id;
                    DB::table('data_shops_enrollments')->where('id', $value->id)
                        ->update(['course_id' => $data_shops_complete->course_id]);
                }
        }
        return 0;
    }
}
