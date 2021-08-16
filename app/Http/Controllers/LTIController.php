<?php

namespace App\Http\Controllers;

use App\Enrollment;
use App\Exceptions\Handler;
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
use \IMSGlobal\LTI;
use App\Custom\LTIDatabase;
use App\Assignment;


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
    public function linkAssignmentToLMS(Request $request, Assignment $assignment)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('linkAssignmentToLMS', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $resource_link_id = $request->resource_link_id;
            DB::table('assignments')
                ->where('id', $assignment->id)
                ->update(['lms_resource_link_id' => $resource_link_id]);

            $response['assignment_id'] = $assignment->id;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error logging you in via LTI.  Please try again by refreshing the page or contact us for assistance.";

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
     * @return Application|RedirectResponse|Redirector
     * @throws Exception
     */
    public function authenticationResponse(Assignment $assignment,
                                           User       $user,
                                           LtiLaunch  $ltiLaunch)
    {

        try {
            $launch = LTI\LTI_Message_Launch::new(new LTIDatabase())
                ->validate();
            if ($launch->is_deep_link_launch()) {
                $resource = LTI\LTI_Deep_Link_Resource::new()
                    ->set_url(request()->getSchemeAndHttpHost() . "/api/lti/redirect-uri")
                    ->set_title('Adapt');
                // file_put_contents(base_path() . '//lti_log.text', print_r((array)$launch->get_deep_link(), true), FILE_APPEND);
                $launch->get_deep_link()
                    ->output_response_form([$resource]);
            }

            $resource_link_id = $launch->get_launch_data()['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id'];
            $launch_id = $launch->get_launch_id();

            $email = $launch->get_launch_data()['email'] ?? null;
            if (!$email) {
                echo "This external tool can only be accessed by a valid student.  It looks like you're trying to access it in Test Student mode.";
                exit;
            }

            $lti_user = $user->where('email', $email)->first();
            if (!$lti_user) {
                $lti_user = User::create([
                    'first_name' => $launch->get_launch_data()['first_name'],
                    'last_name' => $launch->get_launch_data()['given_name'],
                    'email' => $email,
                    'role' => 3,
                    'time_zone' => 'America/Los_Angeles',
                    'email_verified_at' => now(),
                ]);
            }


            session()->put('lti_user_id', $lti_user->id);

            //  file_put_contents(base_path() . '//lti_log.text', "Launch:" . print_r($launch->get_launch_data(), true) . "\r\n", FILE_APPEND);
            //  file_put_contents(base_path() . '//lti_log.text', "Launch:" . print_r($request->all(), true) . "\r\n", FILE_APPEND);


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
                        $lti_launch_by_user_and_assignment->save();
                    } else {
                        $ltiLaunch
                            ->where('user_id', $lti_user->id)
                            ->where('assignment_id', $linked_assignment->id)
                            ->update(['launch_id' => $launch_id]);
                    }

                    $course_id = $linked_assignment->course_id;
                    //enroll them in the course
                    $enrollments = $lti_user->enrollments->pluck('id')->toArray();
                    if (!in_array($course_id, $enrollments)) {
                        $section_id = DB::table('enrollments')->where('course_id', $course_id)
                            ->select('section_id')
                            ->first()
                            ->section_id;
                        $enrollment = new Enrollment();
                        $enrollment->course_id = $course_id;
                        $enrollment->section_id = $section_id;
                        $enrollment->user_id = $lti_user->id;
                        $enrollment->save();
                    }
                }
                dd($launch);
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
        file_put_contents(base_path() . '//lti_log.text', "Launch id: $launch_id", FILE_APPEND);
        $resource = LTI\LTI_Deep_Link_Resource::new()
            ->set_url(request()->getSchemeAndHttpHost() . "/api/lti/redirect-uri")
            ->set_title('Adapt');
       // file_put_contents(base_path() . '//lti_log.text', print_r((array)$launch->get_deep_link(), true), FILE_APPEND);
        $launch->get_deep_link()
            ->output_response_form([$resource]);
    }


}
