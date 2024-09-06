<?php

namespace App\Console\Commands\OneTimers;

use App\BetaAssignment;
use App\BetaCourse;
use App\Course;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class changeToBeta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:toBeta';

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
            $beta_course_id = 4977;
            $alpha_course_id = 2864;
            $alpha_assignments = DB::table('assignments')->where('course_id', $alpha_course_id)->get();
            $beta_assignments = DB::table('assignments')->where('course_id', $beta_course_id)->get();
            foreach ($alpha_assignments as $alpha_assignment) {
                $beta_assignment = $this->getBetaAssignment($beta_assignments, $alpha_assignment);
                if (!$beta_assignment) {
                    throw new Exception ("$alpha_assignment->name does not exist.");
                }
                $alpha_assignment_questions = DB::table('assignment_question')
                    ->where('assignment_id', $alpha_assignment->id)
                    ->select('question_id')
                    ->pluck('question_id')
                    ->toArray();
                $beta_assignment_questions = DB::table('assignment_question')
                    ->where('assignment_id', $beta_assignment->id)
                    ->select('question_id')
                    ->pluck('question_id')
                    ->toArray();
                $alpha_diff = array_diff($alpha_assignment_questions, $beta_assignment_questions);
                if ($alpha_diff) {
                    echo "In alpha but not in beta: $alpha_assignment->id $alpha_assignment->name $beta_assignment->id $beta_assignment->name";
                    print_r($alpha_diff);
                }
                $beta_diff = array_diff($beta_assignment_questions, $alpha_assignment_questions);

                if ($beta_diff) {
                    echo "In beta but not in alpha: $beta_assignment->id $beta_assignment->name $alpha_assignment->id $alpha_assignment->name";
                    print_r($beta_diff);
                }

            }
            $beta_course = Course::find($beta_course_id);
            DB::beginTransaction();
            if (!BetaCourse::where('id', $beta_course_id)->where('alpha_course_id', $alpha_course_id)->first()) {
                $beta_course = BetaCourse::create(['id' => $beta_course_id, 'alpha_course_id' => $alpha_course_id]);
            }
            $beta_course->alpha = 0;
            $beta_course->save();

            foreach ($alpha_assignments as $alpha_assignment) {
                $beta_assignment = $this->getBetaAssignment($beta_assignments, $alpha_assignment);
                if (!BetaAssignment::where('id', $beta_assignment->id)->where('alpha_assignment_id', $alpha_assignment->id)->first()) {
                    BetaAssignment::create(['id' => $beta_assignment->id, 'alpha_assignment_id' => $alpha_assignment->id]);
                }
            }

            DB::commit();
            return 0;
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
    }

    public function getBetaAssignment($beta_assignments, $alpha_assignment)
    {
        foreach ($beta_assignments as $beta_assignment) {
            if ($beta_assignment->name === $alpha_assignment->name) {
                return $beta_assignment;
            }
        }
        return false;
    }
}
