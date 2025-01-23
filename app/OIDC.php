<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OIDC extends Model
{
    /**
     * @var string
     */
    private $username;
    private $password;

    public function __construct()
    {
        parent::__construct();
        if (app()->environment('testing')){
            $username = 'some-username';
            $secret = 'some-secret';
        } else {
            $username = app()->environment('production') ? 'adapt_production' : 'adapt_staging';
            $this->username = $username;
            $this->password = DB::table('key_secrets')->where('key', $username)->first()->secret;
        }
        $this->base_url = app()->environment('production')
            ? 'https://one.libretexts.org/api/v1'
            : 'https://staging.one.libretexts.org/api/v1';
    }

    /**
     * @param string $role
     * @return int
     * @throws Exception
     */
    public function getAdaptRole(string $role)
    {
        $roles = ['instructor' => 2,
            'student' => 3,
            'grader' => 4,
            'question-editor' => 5,
            'tester' => 6];
        if (!isset($roles[$role])) {
            throw new Exception("$role is not yet set up yet.");
        }
        switch ($role) {
            case('instructor'):
                $adapt_role = 2;
                break;
            case('student'):
                $adapt_role = 3;
                break;
            case('grader'):
                $adapt_role = 4;
                break;
            case('question-editor'):
                $adapt_role = 5;
                break;
            case('tester'):
                $adapt_role = 6;
                break;
            default:
                throw new Exception("$role is not yet set up yet.");
        }
        return $adapt_role;
    }

    public function changeEmail(string $central_identity_id, string $email)
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->base_url}/users/$central_identity_id/email-change-direct",
                ['email' => $email]);

        if ($response->successful()) {
            //$response = $response->json(); // if the response is JSON
            // $response['type'] = 'success';
        } else {
            //$response['type'] = 'error';
            // $response['message'] = $response['errors'];
        }
        return $response;

    }

    /**
     * @param array $data
     * @return array|Response|mixed
     */
    public function autoProvision(array $data)
    {


        $auto_provision_response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->base_url}/auth/auto-provision", $data);

        if ($auto_provision_response->successful()) {
            $response = $auto_provision_response->json();
            $response['type'] = 'success';
        } else {
            $response['type'] = 'error';
            $response['message'] = json_encode($auto_provision_response->json());

        }
        return $response;

    }

    /**
     * @param string $email
     * @return array|mixed
     */
    public function getPrincipalAttributes(string $email)
    {

        $principal_attributes_response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->base_url}/users/principal-attributes", [
                'username' => $email,
            ]);
        if ($principal_attributes_response->successful()) {
            $response['principal_attributes'] = $principal_attributes_response->json();
            $response['type'] = 'success';
        } else {
            $response['type'] = 'error';
            $response['message'] = json_encode($principal_attributes_response->json());
        }
        return $response;
    }
}
