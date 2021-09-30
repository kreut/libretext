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

        // file_put_contents(base_path() . '//lti_log.text', "Initiate login request:" . print_r($request->all(), true) . "\r\n", FILE_APPEND);
        LTI\LTI_OIDC_Login::new(new LTIDatabase())
            ->do_oidc_login_redirect(request()->getSchemeAndHttpHost() . "/api/lti/redirect-uri", $request->all())
            ->do_redirect();

    }

    /**
     * @param Assignment $assignment
     * @param User $user
     * @param LtiLaunch $ltiLaunch
     * @param LtiGradePassback $ltiGradePassback
     * @return Application|RedirectResponse|Redirector
     * @throws Exception
     */
    public function authenticationResponse(Assignment       $assignment,
                                           User             $user,
                                           LtiLaunch        $ltiLaunch,
                                           LtiGradePassback $ltiGradePassback)
    {

        try {
            $launch = LTI\LTI_Message_Launch::new(new LTIDatabase())
                ->validate();
            if ($launch->is_deep_link_launch()) {
                //this configures the Deep Link
                $resource = LTI\LTI_Deep_Link_Resource::new()
                    ->set_url(request()->getSchemeAndHttpHost() . "/api/lti/redirect-uri")
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
