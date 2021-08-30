<?php

namespace App\Console\Commands;

use App\DataShop;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\VarDumper\Cloner\Data;


class updateDataShopWithSchoolAndClass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:DatashopsWithSchoolAndClass';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds the school and class to the current datashops';

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
            $question_ids = DB::table('data_shops')->where('school', 'some school')
                ->select('problem_name')
                ->groupBy('problem_name')
                ->get()
                ->pluck('problem_name')
                ->toArray();
            $class_school_assignments = DB::table('assignment_question')
                ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->join('schools', 'courses.school_id', '=', 'schools.id')
                ->whereIn('question_id', $question_ids)
                ->select('question_id', 'course_id', 'assignment_id', 'school_id')
                ->orderBy('assignment_question.id', 'desc')
                ->groupBy('question_id', 'course_id', 'assignment_id', 'school_id')
                ->get();

            $total =  count($class_school_assignments);

            foreach ($class_school_assignments as $key => $value) {
                echo $total - $key . "\r\n";
                DB::table('data_shops')
                    ->where('problem_name', $value->question_id)
                    ->update(['class' => $value->course_id,
                        'school' => $value->school_id,
                        'level' => $value->assignment_id]);
            }
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
    }
}
