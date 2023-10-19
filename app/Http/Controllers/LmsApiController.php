<?php

namespace App\Http\Controllers;

use App\CanvasAPI;
use App\Course;
use App\Exceptions\Handler;
use App\LmsAccessToken;
use App\LmsAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class LmsApiController extends Controller
{
    /**
     * @param Course $course
     * @param LmsAPI $lmsAPI
     * @return array
     * @throws Exception
     */
    public function getOauthUrl(Course $course, LmsAPI $lmsAPI): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getOauthUrl', [$lmsAPI, $course]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $lti_registration = $course->getLtiRegistration();
            if (strpos($lti_registration->iss, 'canvas') === false) {
                $response['type'] = 'info';
                $response['message'] = "$lti_registration->iss does not have API support.";
                return $response;
            }
            $redirect_uri = request()->getSchemeAndHttpHost() . "/instructors/courses/lms/access-granted";
            $scope = '';
            $scopes = ['url:GET|/api/v1/courses',
                'url:GET|/api/v1/courses/:id',
                'url:GET|/api/v1/courses/:course_id/assignments',
                'url:POST|/api/v1/courses/:course_id/assignments',
                'url:PUT|/api/v1/courses/:course_id/assignments/:id',
                'url:DELETE|/api/v1/courses/:course_id/assignments/:id',
                'url:GET|/api/v1/courses/:course_id/assignment_groups',
                'url:POST|/api/v1/courses/:course_id/assignment_groups'];
            foreach ($scopes as $endpoint) {
                $scope .= $endpoint . '%20';
            }
            $scope = substr($scope, 0, -3);//get rid of the last %20
            $response['oauth_url'] = $lti_registration->auth_server . "/login/oauth2/auth?client_id=$lti_registration->api_key&redirect_uri=$redirect_uri&state=$course->id&response_type=code&scope=$scope";
            $file_name = str_replace('https://', '', $lti_registration->auth_server);
            Storage::disk('s3')->put("oauth_urls/$file_name.txt", $response['oauth_url']);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the OAuth URL.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Course $course
     * @param string $authorization_code
     * @return array
     */
    public function getAccessToken(Request $request, Course $course, string $authorization_code): array
    {
        $result['type'] = 'error';
        $lti_registration = $course->getLtiRegistration();
        if (strpos($lti_registration->iss, "canvas") !== false) {
            $canvasAPI = new CanvasAPI($lti_registration);
            $lms_result = $canvasAPI->getAccessToken($authorization_code);
            if ($lms_result['type'] === 'success') {
                LmsAccessToken::updateOrCreate(['user_id' => $request->user()->id,
                    'lms' => 'canvas',
                    'school_id' => $course->school_id],
                    ['access_token' => $lms_result['access_token'],
                        'refresh_token' => $lms_result['refresh_token']]);
                $result['type'] = 'success';
            }
        } else {
            $result['message'] = "We don't yet support $lti_registration->iss for getting access tokens.";
        }
        return $result;
    }

}
