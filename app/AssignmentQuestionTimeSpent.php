<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Traits\DateFormatter;

class AssignmentQuestionTimeSpent extends Model
{
    use DateFormatter;
    protected $guarded = [];

    /**
     * @param Course $course
     * @return array
     */
    public function getTimeSpentByUserAndAssignment(Course $course): array
    {
        $assignment_ids = $course->assignments->pluck('id')->toArray();
        $assignment_time_spents = DB::table('assignment_question_time_spents')
            ->select('user_id', 'assignment_id', DB::raw("SUM(time_spent) as time_spent"))
            ->groupBy('user_id', 'assignment_id')
            ->whereIn('assignment_id', $assignment_ids)
            ->get();
        $formatted_time_spents = [];
        foreach ($assignment_time_spents as $value) {
            $time_spent = $value->time_spent;
            $time_spent = $this->secondsToHoursMinutesSeconds($time_spent);
            $time_spent = "($time_spent)";
            $formatted_time_spents[] = ['user_id' => $value->user_id, 'assignment_id' => $value->assignment_id, 'time_spent' => $time_spent];
        }
        return $formatted_time_spents;
    }
}
