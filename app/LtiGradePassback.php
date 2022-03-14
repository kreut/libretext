<?php

namespace App;

use App\Custom\LTIDatabase;
use App\Exceptions\Handler;
use App\Jobs\ProcessPassBackByUserIdAndAssignment;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Overrides\IMSGlobal\LTI;

class LtiGradePassback extends Model
{
    protected $guarded = [];

    function initPassBackByUserIdAndAssignmentId($score_to_passback, $ltiLaunch)
    {

        try {
            if (!$ltiLaunch) {
                throw new Exception ('LTILaunch was empty; cannot pass back grade.');
            }
            $launch_id = $ltiLaunch->launch_id;
            $user_id = $ltiLaunch->user_id;
            $assignment_id = $ltiLaunch->assignment_id;
            if (!User::find($user_id)->is_fake_student) {
                $this->updateOrCreate(['user_id' => $user_id, 'assignment_id' => $assignment_id],
                    [
                        'launch_id' => $launch_id,
                        'status' => 'pending',
                        'score' => $score_to_passback,
                        'message' => 'none'
                    ]
                );
                if (!app()->environment('testing')) {
                    $ltiGradePassback = new LtiGradePassback();
                    $ltiGradePassback->passBackByUserIdAndAssignmentId($score_to_passback, $ltiLaunch);
                    //ProcessPassBackByUserIdAndAssignment::dispatch($score_to_passback, $ltiLaunch);
                }
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }

    }

    function passBackByUserIdAndAssignmentId($score_to_passback, $ltiLaunch)
    {
        //have this in the code as well just in case I sometimes queue and sometimes don't

        try {
            if (!$ltiLaunch) {
                throw new Exception ('LTILaunch was empty; cannot pass back grade.');
            }

            $launch_id = $ltiLaunch->launch_id;
            $user_id = $ltiLaunch->user_id;
            $assignment_id = $ltiLaunch->assignment_id;

            $launch = LTI\LTI_Message_Launch::from_cache($launch_id, new LTIDatabase());
            $iss = $launch->get_launch_data()['iss'];

            if ($iss === !$launch->has_ags()) {
                throw new Exception ("Don't have grades!");
            }
            $grades = $launch->get_ags();
            $is_canvas = in_array($iss, ["https://canvas.instructure.com", "https://dev-canvas.libretexts.org"]);
            $is_blackboard = $iss === "https://blackboard.com";

            if ($iss === "https://canvas.instructure.com" && !$launch->has_nrps()) {
                throw new Exception("no names and roles");
            }
            $score_maximum = 0 + DB::table('assignment_question')
                    ->where('assignment_id', $assignment_id)
                    ->sum('points');

            //  file_put_contents('/var/www/dev.adapt/lti_log.text', "launch data" . print_r($launch->get_launch_data(), true) . "\r\n", FILE_APPEND);
            $score = LTI\LTI_Grade::new()
                ->set_score_given($score_to_passback)
                ->set_score_maximum($score_maximum)
                ->set_timestamp(date(\DateTime::ISO8601))
                ->set_activity_progress('Completed')
                ->set_grading_progress('FullyGraded')
                ->set_user_id($launch->get_launch_data()['sub']);

            //  file_put_contents('/var/www/dev.adapt/lti_log.text', "Resource ID: " . $launch->get_launch_data()['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id'] . "\r\n", FILE_APPEND);
            $response = $grades->put_grade($score);
            $body = $response['body'];

            $success = !isset($body['errors']);
            $status = $success ? 'success' : 'error';
            $success_message = '';
            if ($success) {
                if ($is_canvas) {
                    $success_message = $body['resultUrl'];
                } else if ($is_blackboard) {
                    $success_message = $body['id'];
                } else {
                    $success_message = "$iss not in database";
                }
            }
            $this->updateOrCreate(['user_id' => $user_id, 'assignment_id' => $assignment_id],
                [
                    'launch_id' => $launch_id,
                    'status' => $status,
                    'score' => $score_to_passback,
                    'message' => $success ? $success_message : serialize($body['errors'])
                ]
            );
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }

    }
}
