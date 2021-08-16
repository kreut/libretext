<?php

namespace App;

use App\Custom\LTIDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use \IMSGlobal\LTI;

class LtiGradePassback extends Model
{
    protected $guarded = [];

    function passBackByUserIdAndAssignmentId(Assignment $assignment, int $user_id, $score_to_passback, LtiLaunch $ltiLaunch)
    {


        //$launch = LTI\LTI_Message_Launch::from_cache($launch_id, new LTIDatabase());
        $launch_id_info = $ltiLaunch->where('assignment_id', $assignment->id)
            ->where('user_id', $user_id)
            ->first();
        if (!$launch_id_info){
            return;
        }
        $launch_id = $launch_id_info->launch_id;
        $user_id = $launch_id_info->user_id;
        $assignment_id = $launch_id_info->assignment_id;

        $launch = LTI\LTI_Message_Launch::from_cache($launch_id, new LTIDatabase());

        if (!$launch->has_ags()) {
            echo "Don't have grades!";
            exit;
        }
        $grades = $launch->get_ags();
        if (!$launch->has_nrps()) {
            echo "no names and roles";
            exit;
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
        $this->updateOrCreate(['user_id' => $user_id, 'assignment_id' => $assignment_id],
            ['launch_id' => $launch_id,
                'success' => $success,
                'message' => $success ? $body['resultUrl'] : serialize($body['errors'])]
        );
    }
}
