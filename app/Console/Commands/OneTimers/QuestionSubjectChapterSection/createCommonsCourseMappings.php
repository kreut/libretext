<?php

namespace App\Console\Commands\OneTimers\QuestionSubjectChapterSection;

use App\Course;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class createCommonsCourseMappings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:CommonsCourseMappings';

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
            $commons_courses = Course::where('user_id', 1377)->get();

            foreach ($commons_courses as $commons_course) {
                DB::table('commons_course_name_mappings')->where('name', $commons_course->name)
                    ->update(['course_id' => $commons_course->id]);
            }
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
