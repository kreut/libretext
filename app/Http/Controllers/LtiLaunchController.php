<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Custom\LTIDatabase;
use App\Exceptions\Handler;
use App\LtiAssignmentsAndGradesUrl;
use App\LtiLaunch;
use App\LtiNamesAndRoles;
use App\LtiNamesAndRolesUrl;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Overrides\IMSGlobal\LTI\LTI_Names_Roles_Provisioning_Service;
use Overrides\IMSGlobal\LTI\LTI_Service_Connector;

class LtiLaunchController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param LtiLaunch $ltiLaunch
     * @param LtiAssignmentsAndGradesUrl $ltiAssignmentsAndGradesUrl
     * @param LTIDatabase $LTIDatabase
     * @param LtiNamesAndRolesUrl $ltiNamesAndRolesUrl
     * @return array
     * @throws Exception
     */
    public function createLtiLaunchIfNeeded(Request                    $request,
                                            Assignment                 $assignment,
                                            LtiLaunch                  $ltiLaunch,
                                            LtiAssignmentsAndGradesUrl $ltiAssignmentsAndGradesUrl,
                                            LTIDatabase                $LTIDatabase,
                                            LtiNamesAndRolesUrl        $ltiNamesAndRolesUrl,
                                            LtiNamesAndRoles           $ltiNamesAndRoles): array
    {
        try {
            $response['type'] = 'error';

            $course = $assignment->course;
            $user = $request->user();
            if (!$user->fake_student
                && $course->lms
                && !$course->lms_only_entry
                && !$ltiLaunch->where('user_id', $user->id)->where('assignment_id', $assignment->id)->exist()) {
                if (!$user->lms_user_id) {
                    $exists_in_lms_roster = $ltiNamesAndRoles->existsInLmsRoster($ltiNamesAndRolesUrl, $LTIDatabase, $course, $user);
                    if (!$exists_in_lms_roster) {
                        $response['redirect'] = "/not-enrolled-in-lms";
                        return $response;
                    }
                }
                $sub = $user->lms_user_id;
                $lti_registration = $course->getLtiRegistration();
                $iss = $lti_registration->iss;
                $lti_assignment_and_grades_url = $ltiAssignmentsAndGradesUrl->where('assignment_id', $assignment->id)->first();
                $campus_id = $lti_registration->campus_id;
                $target_link_uri = request()->getSchemeAndHttpHost() . "/api/lti/redirect-uri/$campus_id";
                $client_id = $lti_registration->client_id;
                if (!$lti_assignment_and_grades_url) {
                    throw new Exception ("Student with user_id {$user->id} cannot have score passed back since there is no lti_assignment_and_grades_url for the assignment $assignment->id.");
                }
                $jwt_body = ['sub' => $sub,
                    'iss' => $iss,
                    "https://purl.imsglobal.org/spec/lti-ags/claim/endpoint" => [
                        "lineitem" => $lti_assignment_and_grades_url->url,
                        "scope" => [
                            "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
                            "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly",
                            "https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly",
                            "https://purl.imsglobal.org/spec/lti-ags/scope/score"
                        ]
                    ],
                    "aud" => $client_id,
                    "https://purl.imsglobal.org/spec/lti/claim/target_link_uri" => $target_link_uri
                ];

                $ltiLaunch = new LtiLaunch();
                $ltiLaunch->user_id = $user->id;
                $ltiLaunch->assignment_id = $assignment->id;
                $ltiLaunch->launch_id = "adapt-{$ltiLaunch->user_id}-" . Str::uuid();
                $ltiLaunch->jwt_body = json_encode($jwt_body);
                $ltiLaunch->save();
                $response['message'] = 'saved launch';
                $response['type'] = 'success';
            } else {
                $response['message'] = 'launch exists';
                $response['type'] = 'info';
            }

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }
        return $response;
    }
}
