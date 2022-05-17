<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnalyticsController extends Controller
{
    function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }

    public function index(Request $request, string $start_date = '', string $end_date = '')
    {
        /*curl -H  "Authorization:Bearer <token>" https://dev.adapt.libretexts.org/api/analytics -o analytics.zip
        Couldn't get this to work on staging (Internal Server error) so moved to dev*/

        if ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token')) {
            if ($start_date) {
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

                $diff = $start_date->diffInDays($end_date);
                if ($diff > 7) {
                    return "Max difference between start and end dates is 7 days.";
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
}
