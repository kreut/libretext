<?php

namespace App\Console\Commands\OneTimers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Course;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class deleteCourseAndAllQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:courseAndAllQuestions';

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
            $course = Course::find(6410);
            echo "Courses: " . Course::count() . "\r\n";
            echo "Assignments: " . Assignment::count() . "\r\n";
            $assignment_ids = $course->assignments->pluck('id');
            $assignment_questions = AssignmentSyncQuestion::whereIn('assignment_id', $assignment_ids)->get();

            echo "Assignment Questions: " . AssignmentSyncQuestion::count() . "\r\n";
            echo "Questions: " . Question::count() . "\r\n";
            foreach ($assignment_questions as $assignment_question) {
                $assignment_question->delete();
                $question = Question::find($assignment_question->question_id);
                $question->delete();;
            }
            echo "After Assignment Questions: " . AssignmentSyncQuestion::count() . "\r\n";
            echo "After Questions: " . Question::count() . "\r\n";
            echo "After Courses: " . Course::count() . "\r\n";
            echo "After Assignments: " . Assignment::count() . "\r\n";
            DB::commit();
            return 1;
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
