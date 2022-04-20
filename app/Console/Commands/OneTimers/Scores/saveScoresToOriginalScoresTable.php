<?php

namespace App\Console\Commands\OneTimers\Scores;

use App\Course;
use App\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class saveScoresToOriginalScoresTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save:ScoresToOriginalScoresTable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Saves auto-graded submission scores to secondary table';

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
        $course_id = 348;
        $course = Course::find($course_id);
        echo "Course: $course->name\r\n";
        $instructor = User::find($course->user_id);
        echo "Instructor: $instructor->first_name $instructor->last_name\r\n";
        $assignments = $course->assignments;
        try {
            DB::beginTransaction();
            foreach ($assignments as $assignment) {
                if (!in_array($assignment->id, [3445, 3443, 3073])) {
                    echo "Assignment $assignment->id\r\n";
                    $submissions = DB::table('submissions')
                        ->where('assignment_id', $assignment->id)->get();
                    foreach ($submissions as $submission) {

                        Log::info(json_encode($submission));
                        DB::table('original_submission_scores')->insert(['submission_id' => $submission->id,
                            'assignment_id' => $assignment->id,
                            'user_id' => $submission->user_id,
                            'email' => User::find($submission->user_id)->email,
                            'original_score' => $submission->score]);

                    }
                    $scores = DB::table('scores')
                        ->where('assignment_id', $assignment->id)
                        ->get();
                    foreach ($scores as $score) {
                        DB::table('original_assignment_scores')->insert(['score_id' => $score->id,
                            'assignment_id' => $assignment->id,
                            'assignment_name' => $assignment->name,
                            'user_id' => $score->user_id,
                            'email' => User::find($score->user_id)->email,
                            'original_score' => $score->score]);
                    }
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
