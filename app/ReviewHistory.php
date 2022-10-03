<?php

namespace App;

use App\Traits\DateFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReviewHistory extends Model
{
    use DateFormatter;

    protected $guarded = [];

    /**
     * @param $user_id
     * @param $assignment_id
     * @param $question_id
     * @return void
     */
    public function store($user_id, $assignment_id, $question_id)
    {
        $this->user_id = $user_id;
        $this->assignment_id = $assignment_id;
        $this->question_id = $question_id;
        $this->save();
        session()->put('review_history', $this->id);
    }

    /**
     * @param Course $course
     * @return array
     */
    public function getTimeInReviewByUserAndAssignment(Course $course): array
    {
        $assignment_ids = $course->assignments->pluck('id')->toArray();

        $time_in_reviews = DB::table('review_histories')
            ->whereIn('assignment_id', $assignment_ids)
            ->select('user_id', 'assignment_id', DB::RAW('TIMESTAMPDIFF(SECOND, created_at, updated_at) AS time_in_review'))
            ->get();
        $time_in_reviews_by_user_assignment = [];
        foreach ($time_in_reviews as $time_in_review) {
            $time_in_reviews_by_user_assignment[$time_in_review->user_id][$time_in_review->assignment_id] =
                isset($time_in_reviews_by_user_assignment[$time_in_review->user_id][$time_in_review->assignment_id])
                    ? $time_in_reviews_by_user_assignment[$time_in_review->user_id][$time_in_review->assignment_id] + $time_in_review->time_in_review
                    : $time_in_review->time_in_review;
        }
        $formatted_time_spents = [];
        foreach ($time_in_reviews_by_user_assignment as $user_id => $assignments) {
            foreach ($assignments as $assignment_id => $time_spent) {
                $time_spent = $this->secondsToHoursMinutesSeconds($time_spent);
                $time_spent = "($time_spent)";
                $formatted_time_spents[] = [
                    'user_id' => $user_id,
                    'assignment_id' => $assignment_id,
                    'time_spent' => $time_spent
                ];
            }
        }

        return $formatted_time_spents;
    }
}
