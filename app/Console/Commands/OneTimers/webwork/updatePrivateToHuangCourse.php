<?php

namespace App\Console\Commands\OneTimers\webwork;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updatePrivateToHuangCourse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:privateToHuangCourse';

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
            $huang_course_questions = DB::table('huang_course_questions')->get();
            echo count($huang_course_questions) . " questions.\r\n";
            DB::beginTransaction();
            foreach ($huang_course_questions as $huang_course_question) {
                $question = Question::find($huang_course_question->question_id);
                $question->technology_iframe = $huang_course_question->technology_iframe;
                $question->technology_id = $huang_course_question->technology_id;
                echo $question->title . "\r\n";
                $question->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
