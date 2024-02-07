<?php

namespace App\Console\Commands\OneTimers\Cecilia;

use App\Assignment;
use App\AssignmentSyncQuestion;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateCeciliaAssignmentPointsForAssignmentsWithNoSubmissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ceciliaAssignmentPointsForAssignmentsWithNoSubmissions';

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

        /*Spanish 1B (Libro Libre) 102 Spring24 ONL --- 2981
SPA101- Spanish 1A (Libro Libre ReMix)_ Hybrid Spring 24 Rosales --- 1645
Spanish 1B (Libro Libre) 102 Spring24 HY --- 1829

Adriana

SPA101- online Spanish 1A (Libro Libre ReMix)_ Aguirre_Spring24 --- 2999
Spanish 1B (Libro Libre) 102 Spring24 ONLINE_AGUIRRE 18050 --- 2996
has_submissions_or_file_submissions
        **/
        try {
            DB::beginTransaction();
            $assignment_course_info = DB::table('assignments')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->whereIn('courses.id', [2981, 1645, 1829, 2999, 2996])
                ->select('courses.name AS course_name', 'assignments.name AS assignment_name', 'assignments.id AS assignment_id')
                ->get();
            foreach ($assignment_course_info as $assignment_info) {
                $assignment = Assignment::find($assignment_info->assignment_id);
                $assignment->points_per_question = 'number of points';
                $assignment->save();
                $original_points = DB::table('assignment_question')
                    ->where('assignment_id', $assignment_info->assignment_id)->sum('points');
                $update_points = !$assignment->hasNonFakeStudentFileOrQuestionSubmissions();
                $num_assignment_questions_updated = $update_points
                    ? AssignmentSyncQuestion::where('assignment_id', $assignment_info->assignment_id)->update(['points' => '5.0000', 'weight' => null])
                    : 0;

                echo $assignment_info->course_name . ' ' . $assignment_info->assignment_name . ' Updated points: ' . $update_points . ' Number question updated: ' . $num_assignment_questions_updated . ' Original points: ' . $original_points . "\r\n";

            }
            DB::commit();


        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
