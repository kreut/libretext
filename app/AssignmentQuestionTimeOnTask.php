<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Traits\DateFormatter;

class AssignmentQuestionTimeOnTask extends Model
{
    use DateFormatter;
    protected $guarded = [];

    /**
     * @param Course $course
     * @return array
     */
    public function getTimeOnTaskByUserAndAssignment(Course $course): array
    {
        $assignment_ids = $course->assignments->pluck('id')->toArray();
        $assignment_time_on_tasks = DB::table('assignment_question_time_on_tasks')
            ->select('user_id', 'assignment_id', DB::raw("SUM(time_on_task) as time_on_task"))
            ->groupBy('user_id', 'assignment_id')
            ->whereIn('assignment_id', $assignment_ids)
            ->get();
        $formatted_time_on_tasks = [];
        foreach ($assignment_time_on_tasks as $value) {
            $time_spent = $value->time_on_task;
            $time_spent = $this->secondsToHoursMinutesSeconds($time_spent);
            $time_spent = "($time_spent)";
            $formatted_time_on_tasks[] = ['user_id' => $value->user_id, 'assignment_id' => $value->assignment_id, 'time_spent' => $time_spent];
        }
        return $formatted_time_on_tasks;
    }

    public function getMeanTimeOnTaskByAssignments(Course $course): array
    {
        $assignment_ids = $course->assignments->pluck('id')->toArray();
        $assignment_mean_time_on_tasks = DB::table('assignment_question_time_on_tasks')
            ->select( 'assignment_id', DB::raw("AVG(time_on_task) as mean_time_on_task"))
            ->groupBy( 'assignment_id')
            ->whereIn('assignment_id', $assignment_ids)
            ->get();
        $formatted_mean_time_on_tasks = [];
        foreach ( $assignment_mean_time_on_tasks as $value) {
            $time_spent = $value->mean_time_on_task;
            $time_spent = $this->secondsToHoursMinutesSeconds($time_spent);
            $formatted_mean_time_on_tasks[] = [ 'id' => $value->assignment_id, 'mean_time_spent' => $time_spent];
        }
        return $formatted_mean_time_on_tasks;
    }







}
