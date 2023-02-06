<?php

namespace App\Helpers;

use App\Question;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;

class Helper
{
    public static function isAdmin(): bool
    {
        return Auth::user() && in_array(Auth::user()->id, [1, 5]);

    }

    /**
     * @return string
     */
    public static function getWebworkCodePath(): string
    {
       return  app()->environment() === 'production' ? "private/ww_files/" : "private/ww_files/" . app()->environment() . "/";
    }

    public static function isMeLoggedInAsAnotherUser($user): bool
    {
        return $user->isMe() && !Helper::isAdmin();
    }

    public static function defaultNonInstructorEditor()
    {
        $id = 0;
        switch (app()->environment()) {
            case('local'):
            case('production'):
                $id = 2000;
                break;
            case('staging'):
                $id = 1738;
                break;
        }

        return app()->environment('testing')
            ? User::where('first_name', 'Default Non-Instructor Editor')->first()
            : User::where('id', $id)->where('role', 5)->first();
    }

    public static function getDefaultAssignTos(int $course_id): array
    {
        return [['groups' => [['value' => ['course_id' => $course_id], 'text' => 'Everybody']],
            'selectedGroup' => '',
            'available_from_date' => Carbon::now()->format('Y-m-d'),
            'available_from_time' => '9:00 AM',
            'due_date' => Carbon::now()->addDay()->format('Y-m-d'),
            'due_time' => '9:00 AM']];
    }

    public static function getQtiQuestionType(string $qti_json)
    {
        $qti_json = json_decode($qti_json, true);
        return isset($qti_json['questionType'])
            ? str_replace('_', ' ', $qti_json['questionType'])
            : 'qti';

    }

    public static function getSubmissionType($value): string
    {

        $submission = [];
        if ($value->technology !== 'text') {
            $submission[] = $value->technology === 'qti' ? Helper::getQtiQuestionType($value->qti_json) : $value->technology;
        }
        if (isset($value->open_ended_submission_type) && $value->open_ended_submission_type) {
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

    public static function createAccessCode($length = 12)
    {
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

    public static function arrayToCsvDownload($array, $file_name)
    {
        header('Content-Encoding: UTF-8');
        header('Content-type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $file_name . '.csv";');
        echo "\xEF\xBB\xBF";
        // open the "output" stream
        // see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
        $f = fopen('php://output', 'w');

        foreach ($array as $line) {
            fputcsv($f, $line);
        }
    }

    /**
     * @param $h5p_id
     * @return mixed
     * @throws Exception
     */
    public static function h5pApi($h5p_id)
    {
        $endpoint = "https://studio.libretexts.org/api/h5p/$h5p_id";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch)

        $output = curl_exec($ch);
        $error_msg = curl_errno($ch) ? curl_error($ch) : '';
        if ($error_msg) {
            throw new Exception ("Getting the H5p info did not work: $error_msg");
        }
        $h5p_object = json_decode($output, 1);

        curl_close($ch);
        return $h5p_object;
    }


}
