<?php

namespace App\Http\Controllers;

use App\Enrollment;
use App\Exceptions\Handler;
use App\LtiGradePassback;
use App\LtiLaunch;
use App\User;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Overrides\IMSGlobal\LTI;
use App\Custom\LTIDatabase;
use App\Assignment;
use Carbon\Carbon;

class LTIController extends Controller
{

    public function jsonConfig($id)
    {
        $app_url = config('app.url');
        $title = config('app.name');
        return [
            'title' => $title,
            'scopes' => [
                0 => 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem',
                1 => 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly',
                2 => 'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly',
                3 => 'https://purl.imsglobal.org/spec/lti-ags/scope/score',
                4 => 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly',
            ],
            'extensions' => [
                0 => [
                    'platform' => 'canvas.instructure.com',
                    'settings' => [
                        'placements' => [
                            0 => [
                                'placement' => 'assignment_selection',
                                'message_type' => 'LtiDeepLinkingRequest',
                                'target_link_uri' => $app_url . '/api/lti/configure/' . $id,
                            ],
                        ],
                        'assignment_selection' => [
                            'placement' => 'assignment_selection',
                            'message_type' => 'LtiDeepLinkingRequest',
                            'target_link_uri' => $app_url . '/api/lti/configure/' . $id,
                        ],
                    ],
                ],
            ],
            'public_jwk' => [
                'd' => 'tkdUVHX4yVKzkK1pPLKO11QXzteTcBF4QJIVGJ6ZjwBf7WeBIXzMrGli2XFSFum2yygrbkQlTF_Xr3yG5JC1NBK4aj4t0AE3Fy_89a_PmwFKa4aTQIPX73zP2bpFw0YHnejDzTAtdZ7HhKfB1FOKBzcF1ci-hb5rLax8mKBJ5IyIjJN-DtjBYwGr6CCYTNIJKF1Z8UT-TDYtZxj1YSvk32cka4ttMdUYdwrCKt-j1MsQiAlpA-437SxqlXUAX7ooutNCz-b-57h8_Sw7AnmO8USbtHi3Q5O__bpG_H7quv_t1WDGAoWFr6cOA2h_Kgx8WX1szMmiOPPZmpdu5YYHcQ',
                'e' => 'AQAB',
                'n' => '8osiSa75nmqmakwNNocLA2N2huWM9At_tjSZOFX1r4-PDclSzxhMw-ZcgHH-E_05Ec6Vcfd75i8Z-Bxu4ctbYk2FNIvRMN5UgWqxZ5Pf70n8UFxjGqdwhUA7_n5KOFoUd9F6wLKa6Oh3OzE6v9-O3y6qL40XhZxNrJjCqxSEkLkOK3xJ0J2npuZ59kipDEDZkRTWz3al09wQ0nvAgCc96DGH-jCgy0msA0OZQ9SmDE9CCMbDT86ogLugPFCvo5g5zqBBX9Ak3czsuLS6Ni9Wco8ZSxoaCIsPXK0RJpt6Jvbjclqb4imsobifxy5LsAV0l_weNWmU2DpzJsLgeK6VVw',
                'p' => '-TEfpa5kz8Y6jCPJK6u5GMBXIniy1972X_HwyuqcUDZDyy2orr3rRj0sOtJoDHtC62_NnrhuvZYyW-cZ0nDzrzPj8ma-gCpbcgdRfOpEAeA6T_xjfN5KN3u3dHQ7e_qoBtCPJFhiB8Axmjs_NdbwUo0axqQB50QpbRv3gdid0qk',
                'q' => '-SuCu0BGnaed3VYa7GBAyNf74eNPSn3Ht9MwK1-9iFmC5T0CULHndUcV4Zzp-qwORSYEW_R2oyfDRM_MRCosSUEiHztMZLglJeZxtBx6MjH6vLaQwW7Ixkg-69kKct8H93tC7YNTqZ14gEwT_wBfmQGqfV6R12KgRJ1KQeSSJ_8',
                'dp' => 'aPCeAjjZ7YHuP_wGCOUNUvYU-8hWkIAtwyPxIpMAdusTS6oTwlrqjK7QRIk9FhyGhv2TWwcSY7avyHIfNrcoeBzjHr7T9MdhsTiRwYgqUZvrEqoX_4rhOFJaZKlaL5DUV-JWlZi-18LBYNEYgoTcufcAUqzYvFrBE1jWt5DQjdk',
                'dq' => 'E7OrDJ9SdhjQ1LWAP3yE4tyhIAVXOa6kYhai0mspk2RwgyvFyReoE4_hXQuJPLbqEfGlmpfD4ba9K-26WxFymwA5cHrB2Zzt4wdLqlAuIVXuW4mb_I-D9Jm1z_RDbT3RZXIropglv12iL5LUae9fn7uP_YXCxmMYBRTi0D8Ah4U',
                'qi' => 'YwLEhy55SQucj2vQqSO1dqn2YiB2ARHBA83QKb1PHflkTNGn3mR_gLow-xU7BmTmA2-9CeDHiJrD181gb48XbI24Nn4QXAjS-mYYIpFASR739UI4W5wyyOCMyFtT6OupEgkqKw_swITU1GHKYI-lB_-y0Q-XSdLmuP6ZkkdAQao',
                'alg' => 'RS256',
                'kid' => '58f36e10-c1c1-4df0-af8b-85c857d1634f',
                'kty' => 'RSA',
                'use' => 'sig',
            ],
            'description' => 'Adapt',
            'custom_fields' => [
            ],
            'target_link_uri' => $app_url . '/api/lti/redirect-uri/' . $id,
            'oidc_initiation_url' => $app_url . '/api/lti/oidc-initiation-url',
        ];

    }

    /**
     * @return array
     * @throws Exception
     */
    public function getUser()
    {
        $response['type'] = 'error';
        try {
            $user = User::where('id', session()->get('lti_user_id'))->firstOrFail();
            $response['token'] = \JWTAuth::fromUser($user);
            $response['type'] = 'success';
        } catch (ModelNotFoundException $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "It looks like your LTI user id was not valid.  Please try the process again or contact us for assistance.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'We could not link up your LMS user account with Adapt.  Please try again or contact us for assistance.';

        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function linkAssignmentToLMS(Request $request, Assignment $assignment): array
    {
        $response['type'] = 'error';
        $lms_resource_link_id = $request->lms_resource_link_id;
        $authorized = Gate::inspect('linkAssignmentToLMS', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $lms_resource_link_id_exists = DB::table('assignments')
            ->where('lms_resource_link_id', $lms_resource_link_id)
            ->first();
        if ($lms_resource_link_id_exists) {
            $response['message'] = "That LMS resource is already linked to another assignment.";
            return $response;
        }

        try {
            DB::table('assignments')
                ->where('id', $assignment->id)
                ->update(['lms_resource_link_id' => $lms_resource_link_id]);

            $response['assignment_id'] = $assignment->id;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error linking the assignment to your LMS.  Please try again by refreshing the page or contact us for assistance.";

        }
        return $response;
    }

    public function initiateLoginRequest(Request $request)
    {
        $campus_id = basename($request['target_link_uri']);
        $launch_url = request()->getSchemeAndHttpHost() . "/api/lti/redirect-uri/$campus_id";
        if ($campus_id === 'configure' || $campus_id === 'redirect-uri') {
            $campus_id = '';
            $launch_url = request()->getSchemeAndHttpHost() . "/api/lti/redirect-uri";
        }

        // file_put_contents(base_path() . '//lti_log.text', "Initiate login request:" . print_r($request->all(), true) . "\r\n", FILE_APPEND);
        LTI\LTI_OIDC_Login::new(new LTIDatabase())
            ->do_oidc_login_redirect($launch_url, $campus_id, $request->all())
            ->do_redirect();

    }

    /**
     * @param Assignment $assignment
     * @param User $user
     * @param LtiLaunch $ltiLaunch
     * @param LtiGradePassback $ltiGradePassback
     * @param string $campus_id
     * @return Application|RedirectResponse|Redirector
     * @throws Exception
     */
    public function authenticationResponse(Assignment       $assignment,
                                           User             $user,
                                           LtiLaunch        $ltiLaunch,
                                           LtiGradePassback $ltiGradePassback,
                                           string           $campus_id = '')
    {

        try {
            $launch = LTI\LTI_Message_Launch::new(new LTIDatabase())
                ->validate();
            $url = $campus_id === ''
                ? request()->getSchemeAndHttpHost() . "/api/lti/redirect-uri"
                : request()->getSchemeAndHttpHost() . "/api/lti/redirect-uri/$campus_id";
            if ($launch->is_deep_link_launch()) {
                //this configures the Deep Link
                $resource = LTI\LTI_Deep_Link_Resource::new()
                    ->set_url($url)
                    ->set_title('Adapt');
                $launch->get_deep_link()
                    ->output_response_form([$resource]);
                exit;
            }

            $launch_id = $launch->get_launch_id();

            $email = $launch->get_launch_data()['email'] ?? null;
            if (!$email) {
                echo "This external tool can only be accessed by a valid student.  It looks like you're trying to access it in Test Student mode.";
                exit;
            }

            $lti_user = $user->where('email', $email)->first();
            if (!$lti_user) {
                $lti_user = User::create([
                    'first_name' => $launch->get_launch_data()['given_name'],
                    'last_name' => $launch->get_launch_data()['family_name'],
                    'email' => $email,
                    'role' => 3,
                    'time_zone' => 'America/Los_Angeles',
                    'email_verified_at' => now(),
                ]);
            }


            session()->put('lti_user_id', $lti_user->id);

            //  file_put_contents(base_path() . '//lti_log.text', "Launch:" . print_r($launch->get_launch_data(), true) . "\r\n", FILE_APPEND);
            //  file_put_contents(base_path() . '//lti_log.text', "Launch:" . print_r($request->all(), true) . "\r\n", FILE_APPEND);

            //if this has not been configured yet, there will be no resource link id
            $resource_link_id = $launch->get_launch_data()['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id'];
            $linked_assignment = $assignment->where('lms_resource_link_id', $resource_link_id)->first();

            if ($linked_assignment) {
                if ($lti_user->role === 3) {
                    $lti_launch_by_user_and_assignment = $ltiLaunch
                        ->where('user_id', $lti_user->id)
                        ->where('assignment_id', $linked_assignment->id)
                        ->first();

                    if (!$lti_launch_by_user_and_assignment) {
                        $lti_launch_by_user_and_assignment = new LtiLaunch();
                        $lti_launch_by_user_and_assignment->user_id = $lti_user->id;
                        $lti_launch_by_user_and_assignment->assignment_id = $linked_assignment->id;
                        $lti_launch_by_user_and_assignment->launch_id = $launch_id;
                        $lti_launch_by_user_and_assignment->jwt_body = json_encode($launch->get_launch_data());
                        $lti_launch_by_user_and_assignment->save();

                        //just in case the instructor changed the LMS late, let's passback any score
                        $score_exists = DB::table('scores')
                            ->where('user_id', $lti_user->id)
                            ->where('assignment_id', $linked_assignment->id)
                            ->first();
                        if ($score_exists) {
                            $ltiGradePassback->initPassBackByUserIdAndAssignmentId($score_exists->score, $lti_launch_by_user_and_assignment);
                        }
                    } else {
                        //use the most recently validated launch information
                        $ltiLaunch
                            ->where('user_id', $lti_user->id)
                            ->where('assignment_id', $linked_assignment->id)
                            ->update(['launch_id' => $launch_id,
                                'jwt_body' => json_encode($launch->get_launch_data())]);
                        $ltiGradePassback->where('user_id', $lti_user->id)
                            ->where('assignment_id', $linked_assignment->id)
                            ->update(['launch_id' => $launch_id]);
                    }
                }
                //TO DO --- why aren' these saved? is it the redirect?
                session()->put('lti_user_id', $lti_user->id);
                $due_date = Carbon::now()->addHour()->toDateTimeString();
                session()->put('lti_due_date', $due_date);
                return redirect("/init-lms-assignment/$linked_assignment->id");

            } else {

                return redirect("/instructors/link-assignment-to-lms/$resource_link_id");
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            echo "There was an error logging you in via LTI.  Please refresh the page and try again or contact us for assistance.";
        }
    }

    public function configure($launch_id)
    {

        $launch = LTI\LTI_Message_Launch::from_cache($launch_id, new LTIDatabase());
        if (!$launch->is_deep_link_launch()) {
            echo "Not a deep link.";
            exit;
        }
        //file_put_contents(base_path() . '//lti_log.text', "Launch id: $launch_id", FILE_APPEND);
        $resource = LTI\LTI_Deep_Link_Resource::new()
            ->set_url(request()->getSchemeAndHttpHost() . "/api/lti/redirect-uri")
            ->set_title('Adapt');
        // file_put_contents(base_path() . '//lti_log.text', print_r((array)$launch->get_deep_link(), true), FILE_APPEND);
        $launch->get_deep_link()
            ->output_response_form([$resource]);
    }


}
