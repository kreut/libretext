<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class addPointsAndScoringTypesToDataShops extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:pointsAndScoringTypesToDataShops';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initializes points and scoring';

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
        $start = microtime(true);
        try {
            $assignments = DB::table('data_shops')
                ->join('assignments', 'data_shops.level', '=', 'assignments.id')
                ->groupBy('level')
                ->get(['level', 'scoring_type']);
            $assignments_by_id = [];
            foreach ($assignments as $assignment) {
                $assignments_by_id[$assignment->level] = $assignment->scoring_type;
            }
            krsort($assignments_by_id);
            foreach ($assignments_by_id as $assignment_id => $scoring_type) {
                echo $assignment_id . "\r\n";
                $total_points = DB::table('assignment_question')
                    ->where('assignment_id', $assignment_id)
                    ->sum('points');
                DB::table('data_shops')
                    ->where('level', $assignment_id)
                    ->update(['level_points' => $total_points, 'level_scoring_type' => $scoring_type]);
            }

            $assignment_questions = DB::table('data_shops')
                ->join('assignment_question', function ($join) {
                    $join->on('data_shops.level', '=', 'assignment_question.assignment_id');
                    $join->on('data_shops.problem_name', '=', 'assignment_question.question_id');
                })
                ->select('problem_name', 'level', 'points')
                ->groupBy(['problem_name', 'level'])
                ->get();
            $count = count($assignment_questions);
            foreach ($assignment_questions as $key => $assignment_question) {
                echo $count - $key . "\r\n";
                DB::table('data_shops')
                    ->where('level', $assignment_question->level)
                    ->where('problem_name', $assignment_question->problem_name)
                    ->update(['problem_points' => $assignment_question->points]);

            }
            echo microtime(true) - $start;
        } catch (Exception $e) {
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
