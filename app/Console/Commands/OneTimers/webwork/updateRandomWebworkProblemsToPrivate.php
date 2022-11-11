<?php

namespace App\Console\Commands\OneTimers\webwork;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateRandomWebworkProblemsToPrivate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:randomWebworkProblemsToPrivate';

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
            $technology_ids = [['old' => '/opt/webwork/courses/huang_course/templates/setChemElementsLab1/1.pg',
                'new' => 'private/setChemElementsLab1/1.pg'],
                ['old' => 'DelmarProblems/setHomework1/problem3.pg', 'new' => 'private/Delmar/setHomework1/problem3.pg'],
                ['old' => 'DelmarProblems/setHomework1/problem4.pg', 'new' => 'private/Delmar/setHomework1/problem4.pg']
                , ['old' => 'setTammy_Sample/blankProblemNandan.pg', 'new' => 'private/setTammy_Sample/blankProblemNandan.pg'],
                ['old' => 'setTammy_Sample/blankProblemNandan2', 'new' => 'private/setTammy_Sample/blankProblemNandan2.pg']];
            $old_technology_ids = [];
            foreach ($technology_ids as $technology_id) {
                $old_technology_ids[] = $technology_id['old'];
            }
            $questions = Question::where('technology', 'webwork')
                ->whereIn('technology_id', $old_technology_ids)
                ->select('id', 'technology_iframe', 'technology_id', 'title')
                ->whereNull('webwork_code')
                ->get();
            echo count($questions) . " questions.\r\n";
            $random_leftover_webwork_questions = DB::table('random_leftover_webwork_questions')
                ->get('question_id')
                ->pluck('question_id')
                ->toArray();
            DB::beginTransaction();
            foreach ($questions as $question) {
                if (!in_array($question->id, $random_leftover_webwork_questions)) {
                    DB::table('random_leftover_webwork_questions')->insert([
                        'question_id' => $question->id,
                        'technology_iframe' => $question->technology_iframe,
                        'technology_id' => $question->technology_id
                    ]);
                }
                $new_technology_id = false;
                foreach ($technology_ids as $technology_id) {
                    if ($technology_id['old'] === $question->technology_id) {
                        $new_technology_id = $technology_id['new'];
                    }
                }
                if (!$new_technology_id) {
                    throw new Exception ("$question->technology_id does not exist.");
                }
                $question->technology_iframe = str_replace($question->technology_id, $new_technology_id, $question->technology_iframe);
                $question->technology_id = str_replace($question->technology_id, $new_technology_id, $question->technology_id);
                echo $question->title . "\r\n";
                $question->save();
                $random_leftover_webwork_questions[] = $question->id;
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
