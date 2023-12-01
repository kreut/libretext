<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class CanvasAPI extends Model
{

    /**
     * @var object
     */
    private $lti_registration;

    public function __construct(object $lti_registration, array $attributes = array())
    {
        $this->lti_registration = $lti_registration;
        parent::__construct($attributes);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    private function _updateAccessToken()
    {
        $lmsAccessToken = new LmsAccessToken();
        $lms_access_token = $lmsAccessToken->where('user_id', request()->user()->id)->first();
        if ($lms_access_token->updated_at <= Carbon::now()->subMinutes(30)->toDateTimeString()) {
            $result = $this->getAccessToken();
            if ($result['type'] === 'success') {
                $lms_access_token->access_token = $result['access_token'];
                $lms_access_token->save();
            } else {
                throw new Exception('Could not create the access token for the API call: ' . $result['message']);
            }
        }
        return $lms_access_token;
    }

    /**
     * @param int $course_id
     * @param array $assignment_info
     * @return array
     * @throws Exception
     */
    public function createAssignment(int $course_id, array $assignment_info): array
    {

        $lms_access_token = $this->_updateAccessToken();
        $external_tool_url = request()->getSchemeAndHttpHost() . "/api/lti/redirect-uri";
        ///When I did Kansas I didn't include a campus id...should probably fix this!!!
        if ($this->lti_registration->campus_id) {
            $external_tool_url .= "/{$this->lti_registration->campus_id}";
        }

      /*  $validate_external_tool_result = $this->_validateExternalTool($lms_access_token->access_token, $course_id, $external_tool_url);
        if ($validate_external_tool_result['type'] === 'error') {
            return $validate_external_tool_result;
        }*/
        $course = Course::where('lms_course_id', $course_id)->first();
        $assignment_info = $course->getIsoStartAndEndDates($assignment_info);
        $url = "/api/v1/courses/$course_id/assignments";
        $data = ['assignment[name]' => $assignment_info['name'],
            'assignment[submission_types][]' => 'external_tool',
            'assignment[points_possible]' => '100',
            'assignment[grading_type]' => 'points',
            'assignment[unlock_at]' => $assignment_info['start_date'],
            'assignment[lock_at]' => $assignment_info['end_date'],
            'assignment[allowed_attempts]' => -1,
            'assignment[assignment_group_id]' => $assignment_info['lms_assignment_group_id'],
            'assignment[external_tool_tag_attributes][url]' => $external_tool_url,
            'assignment[description]' => $assignment_info['instructions'],
            'assignment[external_tool_tag_attributes][new_tab]' => true
        ];
        return $this->_doCurl($lms_access_token->access_token, 'POST', $url, $data);
    }

    /**
     * @param string $access_token
     * @param $course_id
     * @param string $external_tool_url
     * @return array
     * @throws Exception
     */
    private function _validateExternalTool(string $access_token, $course_id, string $external_tool_url): array
    {


        $url = "/api/v1/courses/$course_id/external_tools/sessionless_launch";
        $data = ['url' => $external_tool_url];
        $url = $url . '?' . http_build_query($data);
        return $this->_doCurl($access_token, 'GET', $url, $data);

    }

    /**
     * @param int $course_id
     * @return array
     * @throws Exception
     */
    public function getAssignments(int $course_id): array
    {
        $lms_access_token = $this->_updateAccessToken();
        $url = "/api/v1/courses/$course_id/assignments";
        return $this->_doCurl($lms_access_token->access_token, 'GET', $url);
    }

    /**
     * @param int $course_id
     * @param int $assignment_id
     * @param $assignment_info
     * @return array
     * @throws Exception
     */
    public function updateAssignment(int $course_id, int $assignment_id, $assignment_info): array
    {

        $lms_access_token = $this->_updateAccessToken();
        $url = "/api/v1/courses/$course_id/assignments/$assignment_id";
        $data = [];
        if (isset($assignment_info['name'])) {
            $data['assignment[name]'] = $assignment_info['name'];
        }
        if (isset($assignment_info['instructions'])) {
            $data['assignment[description]'] = $assignment_info['instructions'];
        }
        if (isset($assignment_info['assignment_group_id'])) {
            $data['assignment[assignment_group_id]'] = $assignment_info['lms_assignment_group_id'];
        }
        if (isset($assignment_info['total_points'])) {
            $data['assignment[points_possible]'] = $assignment_info['total_points'];
        }
        if (isset($assignment_info['show_scores'])) {
            $data['assignment[hide_in_gradebook]'] = $assignment_info['show_scores'];
        }
        if (isset($assignment_info['order'])) {
            $data['assignment[position]'] = $assignment_info['order'];
        }
        if (isset($assignment_info['start_date'])) {
            $data['assignment[unlock_at]'] = $assignment_info['start_date'];
        }
        if (isset($assignment_info['end_date'])) {
            $data['assignment[lock_at]'] = $assignment_info['end_date'];
        }
        if (isset($assignment_info['shown'])) {
            $data['assignment[published]'] = $assignment_info['shown'];
        }

        return $this->_doCurl($lms_access_token->access_token, 'PUT', $url, $data);
    }

    /**
     * @param int $course_id
     * @param int $assignment_id
     * @return array
     * @throws Exception
     */
    public function deleteAssignment(int $course_id, int $assignment_id): array
    {

        $lms_access_token = $this->_updateAccessToken();
        $url = "/api/v1/courses/$course_id/assignments/$assignment_id";

        return $this->_doCurl($lms_access_token->access_token, 'DELETE', $url);
    }

    /**
     * @param int $course_id
     * @return array
     * @throws Exception
     */
    public function getCourse(int $course_id): array
    {
        $lms_access_token = $this->_updateAccessToken();
        $url = "/api/v1/courses/$course_id";
        return $this->_doCurl($lms_access_token->access_token, 'GET', $url);
    }


    /**
     * @return array
     * @throws Exception
     */
    public function getCourses(): array
    {
        $lms_access_token = $this->_updateAccessToken();
        $url = "/api/v1/courses?enrollment_type=teacher&per_page=100";
        return $this->_doCurl($lms_access_token->access_token, 'GET', $url);
    }

    /**
     * @param $course_id
     * @return array
     * @throws Exception
     */
    public function getAssignmentGroups($course_id): array
    {
        $lms_access_token = $this->_updateAccessToken();
        $url = "/api/v1/courses/$course_id/assignment_groups";
        return $this->_doCurl($lms_access_token->access_token, 'GET', $url);

    }

    /**
     * @param $course_id
     * @param string $assignment_group
     * @return array
     * @throws Exception
     */
    public function createAssignmentGroup($course_id, string $assignment_group): array
    {
        $lms_access_token = $this->_updateAccessToken();
        $url = "/api/v1/courses/$course_id/assignment_groups";
        return $this->_doCurl($lms_access_token->access_token, 'POST', $url, ['name' => $assignment_group]);

    }

    /**
     * @param string $authorization_code
     * @return array
     */
    public function getAccessToken(string $authorization_code = ''): array
    {

        $api_key = $this->lti_registration->api_key;//from the developer board
        $api_secret = $this->lti_registration->api_secret;
        $result['type'] = 'error';


        $token_url = "{$this->lti_registration->auth_server}/login/oauth2/token";
        $callback_uri = request()->getSchemeAndHttpHost() . "/instructors/courses/lms/access-granted";

        $authorization = base64_encode("$api_key:$api_secret");
        $header = array("Authorization: Basic $authorization", "Content-Type: application/x-www-form-urlencoded");
        if ($authorization_code) {
            $content = "grant_type=authorization_code&code=$authorization_code";
        } else {
            $refresh_token = DB::table('lms_access_tokens')
                ->where('user_id', request()->user()->id)
                ->first()
                ->refresh_token;
            $content = "grant_type=refresh_token&refresh_token=$refresh_token";
        }
        $content .= "&redirect_uri=$callback_uri";

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $token_url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $content
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        if ($response === false) {

            $result['message'] = 'We were not able to connect with Canvas: ' . curl_error($curl) . '.  Please contact us for assistance.';

        } elseif (isset(json_decode($response)->error)) {
            $result['message'] = 'We were not able to connect with Canvas: ' . json_decode($response)->error . '.  Please contact us for assistance.';
        } elseif (json_decode($response) === NULL) {
            $result['message'] = 'We were not able to obtain your access token.  Please contact us for assistance.';
        } else {
            $result['type'] = 'success';
            $result['access_token'] = json_decode($response)->access_token;
            //this will happen the first time.  Then it's reused
            if (isset(json_decode($response)->refresh_token)) {
                $result['refresh_token'] = json_decode($response)->refresh_token;
            }
        }
        return $result;
    }

    /**
     * @param string $access_token
     * @param string $type
     * @param string $url
     * @param array $data
     * @return array
     * @throws Exception
     */
    private function _doCurl(string $access_token, string $type, string $url, array $data = []): array
    {
        $response['type'] = 'error';
        $ch = curl_init();
        switch ($type) {
            case("GET"):
                break;
            case('POST'):
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case('PUT'):
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case('DELETE'):
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            default:
                throw new Exception("Not a valid type for the Canvas API cURL");
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $authorization = "Authorization: Bearer $access_token"; // Prepare the authorisation token

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', $authorization]); // Inject the token into the header
        curl_setopt($ch, CURLOPT_URL, $this->_buildUrl($url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch); // Execute the cURL statement
        if ($result === false) {
            $response['message'] = 'Connection issue: ' . curl_error($ch);

        } else {
            $original_result = $result;
            $result = json_decode($result);
            if ($result === null) {
                $response['message'] = $original_result;
            } else if (is_object($result) && property_exists($result, 'errors')) {
                $curl_error = json_encode($result->errors);
                $response['message'] = "Canvas cURL error: $curl_error";
            } else {
                $response['type'] = 'success';
                $response['message'] = $result;
            }
        }
        curl_close($ch);
        return $response;
    }

    /**
     * @param $url
     * @return string
     */
    private function _buildUrl($url): string
    {
        return $this->lti_registration->auth_server . $url;
    }


}
