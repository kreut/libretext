<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Course;
use App\Helpers\Helper;
use App\LearningOutcome;
use App\Score;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnalyticsController extends Controller
{
    /**
     * @param $date
     * @param string $format
     * @return bool
     */
    function validateDate($date, string $format = 'Y-m-d'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }

    /**
     * @param Request $request
     * @return LearningOutcome[]|\Illuminate\Database\Eloquent\Collection|string
     */
    public function learningOutcomes(Request $request)
    {
        if (Helper::isAdmin() || ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token'))) {
            return LearningOutcome::select('id', 'subject', 'topic', 'description')->get();
        } else {
            return 'Not authorized.';
        }
    }

    /**
     * @param Request $request
     * @return Collection|string
     */
    public function questionLearningOutcome(Request $request)
    {
        if (Helper::isAdmin() || ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token'))) {
            return DB::table('question_learning_outcome')->select('question_id', 'learning_outcome_id')->get();
        } else {
            return 'Not authorized.';
        }
    }


    public function scoresByCourse(Request $request, Course $course, Score $score): array
    {

        //curl -H  "Authorization:Bearer <token>" https://adapt.libretexts.org/api/analytics/scores/course/{course}
        if ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token')) {
            $course_scores = $score->getCourseScores($course, 0);

            $download_rows = $course_scores['download_rows'];
            $download_fields = $course_scores['download_fields'];
            usort($download_rows, function ($a, $b) {
                return $a[0] <=> $b[0];
            });
            array_unshift($download_rows, $download_fields);

            $z_score_key = '';
            foreach ($download_rows[0] as $key => $value) {
                if ($value === 'Z-Score') {
                    $z_score_key = $key;
                }
            }
            if (!$z_score_key) {
                return ['error' => "Could not find the z-score."];
            }
            $analytics_info = [];
            foreach ($download_rows as $key => $download_row) {
                foreach ($download_row as $download_row_key => $value) {
                    if (!in_array($download_row_key, [0, 1, 2, 3, 4, 6, $z_score_key])) {
                        $analytics_info[$key][] = $value;
                    }

                }
            }
            return $analytics_info;
        } else {
            return ['error' => "Not authorized."];
        }

    }

    /**
     * @param Request $request
     * @param Course $course
     * @return Collection|string
     */
    public function proportionCorrectByAssignment(Request $request, Course $course)
    {

        //curl -k -H  "Authorization:Bearer <token>" https://local.adapt:8890/api/analytics/proportion-correct-by-assignment/course/415
        //curl -H  "Authorization:Bearer <token>" https://adapt.libretexts.org/api/analytics/proportion-correct-by-assignment/course/415
        if ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token')) {
            $assignment_ids = $course->assignments->pluck('id')->toArray();
            $scores = DB::table('scores')
                ->join('assignments', 'scores.assignment_id', '=', 'assignments.id')
                ->join('users', 'scores.user_id', '=', 'users.id')
                ->whereIn('assignment_id', $assignment_ids)
                ->select('assignment_id', 'email', 'score', 'users.id AS user_id', 'first_name', 'last_name', 'name')
                ->get();
            $randomizations = DB::table('assignments')->
            whereIn('id', $assignment_ids)
                ->whereNotNull('number_of_randomized_assessments')
                ->get();

            $assignment_question_num_questions = DB::table('assignment_question')
                ->groupBy('assignment_id')
                ->selectRaw('count(*) as count, assignment_id')
                ->whereIn('assignment_id', $assignment_ids)
                ->pluck('count', 'assignment_id');

            $assignment_question_points = DB::table('assignment_question')
                ->groupBy('assignment_id')
                ->selectRaw('sum(points) as sum, assignment_id')
                ->whereIn('assignment_id', $assignment_ids)
                ->pluck('sum', 'assignment_id');

            foreach ($assignment_question_num_questions as $assignment_id => $num_questions) {
                if (isset($randomizations[$assignment_id])) {
                    $proportion_answered_by_student = $randomizations[$assignment_id] / $assignment_question_num_questions[$assignment_id];
                    $assignment_question_points[$assignment_id] = $proportion_answered_by_student * $assignment_question_points[$assignment_id];
                }
            }
            foreach ($scores as $key => $value) {
                $assignment_id = $value->assignment_id;
                $scores[$key]->proportion_correct = Helper::removeZerosAfterDecimal(round($value->score / $assignment_question_points[$assignment_id], 4));
                unset($scores[$key]->user_id);
                unset($scores[$key]->score);
            }
            return $scores;
        } else {
            return
                'Not authorized to get proportion correct.';
        }
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @return Collection|string
     */
    public function getReviewHistoryByAssignment(Request $request, Assignment $assignment)
    {
        //curl -H  "Authorization:Bearer <token>" https://adapt.libretexts.org/api/analytics/review-history/assignment/{assignment}

        if (($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token'))) {
            return DB::table('review_histories')
                ->join('users', 'review_histories.user_id', '=', 'users.id')
                ->select('users.email',
                    'review_histories.assignment_id',
                    'review_histories.question_id',
                    'review_histories.created_at',
                    'review_histories.updated_at')
                ->where('assignment_id', $assignment->id)
                ->get();
        } else {
            return
                'Not authorized.';
        }
    }

    public function index(Request $request, string $start_date = '', string $end_date = '')
    {
        /*curl -H  "Authorization:Bearer <token>" https://dev.adapt.libretexts.org/api/analytics -o analytics.zip
        Couldn't get this to work on staging (Internal Server error) so moved to dev*/

        if (Helper::isAdmin() || ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token'))) {
            if ($start_date) {
                if ($invalid_date = $this->invalidDate($start_date, $end_date, 7)) {
                    return $invalid_date;
                }
                $query_by_date = DB::table('data_shops')
                    ->where('time', '>=', $start_date)
                    ->where('time', '<=', $end_date)->get();
                return json_encode($query_by_date);
            }
            return Storage::disk('backup_s3')->get('analytics.zip');
        } else {
            return
                'Not authorized.';
        }

    }

    /**
     * @param Request $request
     * @param string $start_date
     * @param string $end_date
     * @return false|string
     */
    public function enrollments(Request $request, string $start_date = '', string $end_date = '')
    {
        /*curl -H  "Authorization:Bearer <token>" https://dev.adapt.libretexts.org/api/analytics
        Couldn't get this to work on staging (Internal Server error) so moved to dev*/
        $query = DB::table('enrollments')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->select('email', 'course_id AS class', 'enrollments.created_at')
            ->where('users.fake_student', 0);
        if ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token')) {
            if ($start_date) {
                if ($invalid_date = $this->invalidDate($start_date, $end_date)) {
                    return $invalid_date;
                }
                $query = $query
                    ->where('enrollments.created_at', '>=', $start_date)
                    ->where('enrollments.created_at', '<=', $end_date);

            }
            return json_encode($query->get());
        } else {
            return
                'Not authorized.';
        }

    }

    /**
     * @param $start_date
     * @param $end_date
     * @param bool $max_diff
     * @return false|string
     */
    public function invalidDate($start_date, $end_date, bool $max_diff = false)
    {
        if (!$this->validateDate($start_date)) {
            return "$start_date is not of the form YYY-mm-dd.";
        }
        if (!$end_date) {
            return "You need an end date.";
        }
        if (!$this->validateDate($end_date)) {
            return "$end_date is not of the form YYY-mm-dd.";
        }

        if ($start_date > $end_date) {
            return "Your start date should be before your end date.";
        }
        $start_date = Carbon::parse($start_date);
        $end_date = Carbon::parse($end_date);

        if ($max_diff) {
            $diff = $start_date->diffInDays($end_date);
            if ($diff > $max_diff) {
                return "Max difference between start and end dates is 7 days.";
            }
        }
        return false;
    }
}
