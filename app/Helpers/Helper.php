<?php

namespace App\Helpers;

use App\Discussion;
use App\DiscussionComment;
use App\QuestionMediaUpload;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Parser;
use phpcent\Client;

class Helper
{

    public static function isAdmin(): bool
    {
        $admins = DB::table('admin_emails')
            ->select('email')
            ->get()
            ->pluck('email')->toArray();
        if (app()->environment('local', 'testing')) {
            $admins[] = 'me@me.com';
        }
        return Auth::user()
            && (in_array(Auth::user()->email, $admins)
                || in_array(session()->get('original_email'), $admins));//get the original email since they may be in student view
    }


    /**
     * @return string
     */
    public static function getWebworkCodePath(): string
    {
        return app()->environment() === 'production' ? "private/ww_files/" : "private/ww_files/" . app()->environment() . "/";
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
        if (isset($qti_json['questionType'])) {
            switch ($qti_json['questionType']) {
                case('select_choice'):
                    if (isset($qti_json['dropDownCloze']) && $qti_json['dropDownCloze']) {
                        $qti_json['questionType'] = 'drop down cloze';
                    }
                    break;
                default:
                    break;
            }
        }
        $question_type = 'qti';
        if (isset($qti_json['questionType'])) {
            $question_type = str_replace('_', ' ', $qti_json['questionType']);
            $question_type = str_replace('drop down', 'drop-down', $question_type);

        }
        return $question_type;

    }

    public
    static function getSubmissionType($value): string
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


    public
    static function isAnonymousUser(): bool
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

    public
    static function getCompletionScoringMode($scoring_type, $completion_scoring_mode, $completion_split_auto_graded_percentage): ?string
    {
        if ($scoring_type === 'c') {
            return $completion_scoring_mode === '100% for either'
                ? $completion_scoring_mode
                : "$completion_split_auto_graded_percentage% for auto-graded";
        } else return null;
    }

    public static function getLinkedAccounts(int $user_id)
    {
        $user = User::find($user_id);
        $linked_accounts = [];

        $linked_from_user = DB::table('linked_accounts')
            ->join('users', 'linked_accounts.linked_to_user_id', '=', 'users.id')
            ->select('linked_accounts.user_id')
            ->where('linked_to_user_id', $user->id)
            ->first();
        if ($linked_from_user) {
            $linked_from_user_id = $linked_from_user->user_id;
        } else {
            $linked_from_user = DB::table('linked_accounts')->where('user_id', $user_id)->first();
            if (!$linked_from_user) {
                return json_encode([]);
            } else {
                $linked_from_user_id = $linked_from_user->user_id;
            }
        }

        $linked_from_user = User::find($linked_from_user_id);
        $linked_to_users = DB::table('linked_accounts')
            ->join('users', 'linked_accounts.linked_to_user_id', '=', 'users.id')
            ->where('user_id', $linked_from_user_id)
            ->select('users.*')
            ->get();
        $linked_accounts[] = [
            'id' => $linked_from_user->id,
            'email' => $linked_from_user->email,
            'main_account' => true];
        foreach ($linked_to_users as $linked_to_user) {
            $linked_accounts[] = ['id' => $linked_to_user->id,
                'email' => $linked_to_user->email,
                'main_account' => false];
        }
        return json_encode($linked_accounts);
    }

    public
    static function createAccessCode($length = 12)
    {
        return substr(sha1(mt_rand()), 17, $length);
    }

    public
    static function csvToArray($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;
        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false) {
            $bom = fread($handle, 3);
            if ($bom === b"\xEF\xBB\xBF") {
                fseek($handle, 3);
            } else {
                fseek($handle, 0);
            }
            while (($row = fgetcsv($handle, null, $delimiter)) !== false) {
                if (!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }
        return $data;
    }

    public
    static function arrayToCsvDownload($array, $file_name)
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
    public
    static function h5pApi($h5p_id)
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

    /**
     * @param int $assignment_id
     * @param int $question_id
     * @return void
     * @throws Exception
     */
    public static function removeAllStudentSubmissionTypesByAssignmentAndQuestion(int $assignment_id, int $question_id)
    {
        DB::table('submissions')->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->delete();
        DB::table('submission_files')->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->delete();
        $questionMediaUpload = new QuestionMediaUpload();
        $discussions = Discussion::where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->get();
        foreach ($discussions as $discussion) {
            $discussion_comments = DiscussionComment::where('discussion_id', $discussion->id)->get();
            foreach ($discussion_comments as $discussion_comment) {
                $file = $discussion_comment->file;
                if ($file) {
                    if (Storage::disk('s3')->exists("{$questionMediaUpload->getDir()}/$file")) {
                        Storage::disk('s3')->delete("{$questionMediaUpload->getDir()}/$file");
                    }
                    if ($discussion_comment->transcript) {
                        $vtt_file = $questionMediaUpload->getVttFileNameFromS3Key($file);
                        Storage::disk('s3')->delete("{$questionMediaUpload->getDir()}/$vtt_file");
                    }
                }
                $discussion_comment->delete();
            }
            $discussion->delete();
        }
    }

    /**
     * @return Client
     */
    public static function centrifuge(): Client
    {
        $client = new Client(Helper::centrifugeUrl());
        $client->setApiKey(config('myconfig.centrifugo_api_key'));//from the config json file
        return $client;
    }

    /**
     * @return string
     */
    public static function centrifugeUrl(): string
    {
        $protocol = app()->environment('local') ? "http://" : "https://";
        return $protocol . config('myconfig.centrifugo_domain') . "/api";
    }

    /**
     * @return string
     */
    public static function iMathASDomain(): string
    {

        return in_array(app()->environment(), ['dev', 'local']) ? 'dev2.imathas.libretexts.org' : 'imathas.libretexts.org';
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function schemaAndHost(): string
    {
        switch (app()->environment()) {
            case('local'):
                $schema_and_host = 'https://local.adapt:8891/';
                break;
            case('staging'):
                $schema_and_host = 'https://staging-adapt.libretexts.org/';
                break;
            case('production'):
                $schema_and_host = 'https://adapt.libretexts.org/';
                break;
            default:
                throw new Exception (app()->environment() . ' is not a valid environment to the pending revision notifications.');
        }
        return $schema_and_host;
    }

    /**
     * @param $command
     * @return array
     * @throws Exception
     */
    public static function runFfmpegCommand($command): array
    {
        $descriptorspec = [
            1 => ['pipe', 'w'],  // stdout is a pipe that the child will write to
            2 => ['pipe', 'w'],  // stderr is a pipe that the child will write to
        ];

        $process = proc_open($command, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            throw new Exception("Failed to start ffmpeg process");
        }

        $output = stream_get_contents($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnValue = proc_close($process);

        return [$returnValue, $output, $errorOutput];
    }

    /**
     * @return string
     */

    public static function defaultDiscussItSettings(): string
    {
        return '{"number_of_groups":"1","auto_grade":"0","response_modes":[],"completion_criteria":"1","students_can_edit_comments":"1","students_can_delete_comments":"0","min_number_of_initiated_discussion_threads":"1","min_number_of_replies":"1","min_number_of_initiate_or_reply_in_threads":"1","min_number_of_words":"","min_length_of_audio_video":""}';
    }

    /**
     * @param string $original_discuss_it_settings
     * @return false|string
     */
    public static function makeDiscussItSettingsBackwardsCompatible(string $original_discuss_it_settings): string
    {
        $discuss_it_settings = json_decode($original_discuss_it_settings, 1);
        if (isset($discuss_it_settings['min_number_of_comments'])) {
            $min_number_of_comments = $discuss_it_settings['min_number_of_comments'];
            if (+$min_number_of_comments > 0) {
                $new_val = Round($min_number_of_comments / 2);
                $discuss_it_settings['min_number_of_initiated_discussion_threads'] = $new_val;
                $discuss_it_settings['min_number_of_replies'] = $new_val;
            }
            unset($discuss_it_settings['min_number_of_comments']);
        }
        return json_encode($discuss_it_settings);

    }

    /**
     * @param Request $request
     * @param string $bearer_token
     * @return array
     * @throws Exception
     */
    public static function authorizedLibreOneClaims(Request $request, string $bearer_token): array
    {
        if (!$request->bearerToken()) {
            throw new Exception ('Missing Bearer Token.');
        }
        $token = $request->bearerToken();
        $key = new HmacKey($bearer_token);
        $signer = new HS256($key);
        $parser = new Parser($signer);
        return $parser->parse($token);
    }

}
