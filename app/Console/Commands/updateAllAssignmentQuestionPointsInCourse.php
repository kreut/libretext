<?php

namespace App\Console\Commands;

use App\Assignment;
use App\AssignmentSyncQuestion;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateAllAssignmentQuestionPointsInCourse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:allAssignmentQuestionPointsInCourse {course_id} {points} {test}';

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
            $course_id = $this->argument('course_id');
            $points = $this->argument('points');
            $test = $this->argument('test');
            if (!in_array($test, [0, 1])) {
                echo "test parameter is required.";
                exit;
            }
            $course = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('courses.id', $course_id)
                ->first();

            $assignments = Assignment::where('course_id', $course_id)->get();
            echo "Course: $course->name";
            echo "Instructor: $course->first_name $course->last_name";
            if ($test) {
                exit;
            }
            DB::beginTransaction();
            $total = 0;
            foreach ($assignments as $assignment) {
                $num_updated = AssignmentSyncQuestion::where('assignment_id', $assignment->id)
                    ->update(['points' => $points]);
                $assignment->update(['points_per_question' => 'number of points']);
                echo "$assignment->name: $num_updated updated\r\n";
                $total += $num_updated;
            }
            echo "$total updated.";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
