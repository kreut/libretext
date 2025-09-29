<?php

namespace App\Console\Commands\OneTimers;

use App\AssignmentSyncQuestion;
use App\Course;
use App\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixThuQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:ThuQuestions';

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
            $course = Course::find(7385);
            $assignment_ids = $course->assignments->pluck('id')->toArray();
            $question_ids = DB::table('assignment_question')
                ->whereIn('assignment_id', $assignment_ids)
                ->select('question_id')
                ->get()
                ->pluck('question_id')
                ->toArray();
            $questions = Question::whereIn('id', $question_ids)->get();
            DB::beginTransaction();
            foreach ($questions as $question) {
                $question->clone_source_id = null;
                $question->title = trim(str_replace('copy', '', $question->title));
                $question->save();
            }
            DB::commit();
            echo "Number of questions updated: " . count($questions);
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
