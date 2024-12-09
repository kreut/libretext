<?php

namespace App;

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
        $username = app()->environment('production') ? 'adapt_production' : 'libreone_staging';
        $this->username = $username;
        $this->password = DB::table('key_secrets')->where('key', $username)->first()->secret;
        $this->base_url = app()->environment('production')
            ? 'https://one.libretexts.org/api/v1'
            : 'https://staging.one.libretexts.org/api/v1';
    }

    public function changeEmail(string $central_identity_id, string $email)
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->base_url}/users/$central_identity_id/email-change-direct",
                ['email' => $email]);

        if ($response->successful()) {
            $response = $response->json(); // if the response is JSON
            $response['type'] = 'success';
        } else {
            $response['type'] = 'error';
            $response['message'] = $response['errors'];
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
}
