<?php

namespace App\Console\Commands\Analytics;

use App\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixFormativeAssignmentIdQuestionId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:formativeAssignmentIdQuestionId';

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
        $data_shops_enrollments = DB::table('data_shops_enrollments')->get();
        foreach ($data_shops_enrollments as $value) {
            $course = Course::find($value->course_id);
            if ($course && $course->formative) {
                echo $course->name . "\r\n";
                $assignment = $course->assignments->first();
                $assignment_question = DB::table('assignment_question')->where('assignment_id', $assignment->id)->first();
                DB::table('data_shops_enrollments')->where('id', $value->id)
                    ->update(['assignment_id' => $assignment->id, 'question_id' => $assignment_question->question_id]);

            }
        }
        return 0;
    }
}
