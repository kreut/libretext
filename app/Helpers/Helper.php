<?php

namespace App\Helpers;

use App\User;
use Illuminate\Support\Facades\Auth;

class Helper
{
    public static function isAdmin(): bool
    {
        return Auth::user() && in_array(Auth::user()->id, [1, 5]);

    }

    public static function defaultNonInstructorEditor()
    {
        $id = 0;
        switch (app()->environment()) {
            case('local'):
                $id = 1978;
                break;
            case('staging'):
                $id = 1738;
                break;
            case('production'):
                $id = 2000;
                break;
        }

        return app()->environment('testing')
            ? User::where('first_name', 'Default Non-Instructor Editor')->first()
            : User::where('id', $id)->where('role', 5)->first();
    }

    public static function getSubmissionType($value): string
    {
        $submission = [];
        if ($value->technology !== 'text') {
            $submission[] = $value->technology;
        }
        if ($value->open_ended_submission_type) {
            $submission[] = ucwords($value->open_ended_submission_type);
        }
        if (!$submission) {
            $submission = ['Nothing to submit'];
        }
        return implode(', ', $submission);
    }


    public static function isAnonymousUser(): bool
    {
        return Auth::user() && Auth::user()->email === 'anonymous';
    }

    public
    static function isCommonsCourse($course): bool
    {
        return User::find($course->user_id)->email === 'commons@libretexts.org';
    }

    public
    static function hasAnonymousUserSession(): bool
    {
        return session()->has('anonymous_user') && session()->get('anonymous_user');
    }

    public
    static function removeZerosAfterDecimal($num)
    {
        $pos = strpos($num, '.');
        if ($pos === false) { // it is integer number
            return $num;
        } else { // it is decimal number
            return rtrim(rtrim($num, '0'), '.');
        }
    }

    public static function getCompletionScoringMode($scoring_type, $completion_scoring_mode, $completion_split_auto_graded_percentage): ?string
    {
        if ($scoring_type === 'c') {
            return $completion_scoring_mode === '100% for either'
                ? $completion_scoring_mode
                : "$completion_split_auto_graded_percentage% for auto-graded";
        } else return null;
    }

    public static function createAccessCode($length = 12) {
        return substr(sha1(mt_rand()), 17, $length);
    }

    public static function csvToArray($filename = '', $delimiter = ',')
{
    if (!file_exists($filename) || !is_readable($filename))
        return false;
    $header = null;
    $data = array();
    if (($handle = fopen($filename, 'r')) !== false) {
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
            if (!$header)
                $header = $row;
            else
                $data[] = array_combine($header, $row);
        }
        fclose($handle);
    }
    return $data;
}


}
