<?php

namespace App\Console\Commands\OneTimers\webwork;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateHuangCourseToPrivate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:huangCourseToPrivate';

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
            $questions = Question::where('technology', 'webwork')
                ->where('technology_id', 'LIKE', 'huang_course/%')
                ->select('id', 'technology_iframe', 'technology_id','title')
                ->get();
            echo count($questions) . " questions.\r\n";
            $huang_course_questions = DB::table('huang_course_questions')
                ->get('question_id')
                ->pluck('question_id')
                ->toArray();
            DB::beginTransaction();
            foreach ($questions as $question) {
                if (!in_array($question->id, $huang_course_questions)) {
                    DB::table('huang_course_questions')->insert([
                        'question_id' => $question->id,
                        'technology_iframe' => $question->technology_iframe,
                        'technology_id' => $question->technology_id
                    ]);
                }
                $question->technology_iframe = str_replace('huang_course', 'private', $question->technology_iframe);
                $question->technology_id = str_replace('huang_course', 'private', $question->technology_id);
                echo $question->title . "\r\n";
                $question->save();
                $huang_course_questions[] = $question->id;
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
