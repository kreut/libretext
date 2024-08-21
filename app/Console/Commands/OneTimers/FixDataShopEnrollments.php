<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDataShopEnrollments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:DataShopEnrollments';

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
            $data_shops = DB::table('data_shops_enrollments')
                ->where('instructor_name', "Instructor Kean")
                ->get();
            foreach ($data_shops as $data_shop) {
                $course_info = DB::table('courses')
                    ->join('users', 'courses.user_id', '=', 'users.id')
                    ->join('schools', 'courses.school_id', '=', 'schools.id')
                    ->select('term',
                        'courses.name AS course_name',
                        'schools.name AS school_name',
                        DB::raw('CONCAT(first_name, " " , last_name) AS instructor_name'))
                    ->where('courses.id', $data_shop->course_id)
                    ->first();
                if (!$course_info) {
                    echo $data_shop->course_id. "\r\n";
                } else {
                    $data = [
                        'course_name' => $course_info->course_name,
                        'school_name' => $course_info->school_name,
                        'term' => $course_info->term,
                        'instructor_name' => $course_info->instructor_name];
                    DB::table('data_shops_enrollments')->where('id', $data_shop->id)->update($data);
                }
            }
            DB::commit();
            echo "Done!";
            return 0;
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
    }
}
