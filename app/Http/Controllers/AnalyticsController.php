<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\LearningOutcome;
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
