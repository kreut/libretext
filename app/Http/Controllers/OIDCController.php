<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Exceptions\InvalidSignatureException;
use MiladRahimi\Jwt\Exceptions\InvalidTokenException;
use MiladRahimi\Jwt\Exceptions\JsonDecodingException;
use MiladRahimi\Jwt\Exceptions\SigningException;
use MiladRahimi\Jwt\Exceptions\ValidationException;
use MiladRahimi\Jwt\Parser;
use stdClass;

class OIDCController extends Controller
{
    /**
     * @var Application|UrlGenerator|string
     */
    private $callbackUrl;
    /**
     * @var string
     */
    private $baseUrl;
    /**
     * @var string
     */
    private $authorizeUrl;
    /**
     * @var string
     */
    private $tokenUrl;
    /**
     * @var string
     */
    private $profileUrl;
    /**
     * @var string
     */
    private $logoutUrl;
    /**
     * @var string
     */
    private $jwksUrl;
    /**
     * @var mixed|string
     */
    private $client_id;
    /**
     * @var mixed|string
     */
    private $bearer_token;
    /**
     * @var mixed|string
     */
    private $client_secret;

    public function __construct()
    {

        $this->callbackUrl = url('api/oauth/libreone/callback');
        $this->baseUrl = app()->environment('production')
            ? 'https://auth.libretexts.org'
            : 'https://castest2.libretexts.org';
        $this->authorizeUrl = "$this->baseUrl/cas/oidc/authorize";
        $this->tokenUrl = "$this->baseUrl/cas/oidc/accessToken";
        $this->profileUrl = "$this->baseUrl/cas/oidc/profile";
        $this->logoutUrl = "$this->baseUrl/cas/logout";
        $this->jwksUrl = "$this->baseUrl/cas/oidc/jwks";
        $credentials = DB::table('oidc_credentials')->first();
        $this->client_id = '';
        $this->bearer_token = '';
        $this->client_secret = '';
        if ($credentials) {
            $this->client_id = $credentials->client_id;
            $this->bearer_token = $credentials->bearer_token;
            $this->client_secret = $credentials->client_secret;
        }

    }

    public function redirect()
    {
        $oidcAuth = $this->authorizeUrl;
        $params = [
            'client_id' => $this->client_id,
            'response_type' => 'code',
            'scope' => 'openid profile email libretexts',
            'redirect_uri' => $this->callbackUrl,
            'state' => csrf_token()
        ];

        return redirect($oidcAuth . '?' . http_build_query($params));
    }

    /**
     * @param Request $request
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function instructorVerified(Request $request, User $user): array
    {
        try {
            $response['type'] = 'error';
            $claims = $this->_hasAccess($request);
            $instructor = $user->where('central_identity_id', $claims['central_identity_id'])->first();
            $instructor->verify_status = $claims['verify_status'];
            $instructor->save();
            $response['type'] = 'success';
            $response['verify_status'] = $claims['verify_status'];
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function newUserCreated(Request $request, User $user): array
    {
        try {
            $response['type'] = 'error';
            $claims = $this->_hasAccess($request);
            $email = $claims['email'];
            $new_user = $user->where('email', $email)->first();
            if ($new_user) {
                throw new Exception("$new_user with email $email already exists in ADAPT.");
            } else {
                $new_user = new User();
                $new_user->time_zone = $claims['time_zone'];
                $new_user->central_identity_id = $claims['central_identity_id'];
                $new_user->first_name = $claims['first_name'];
                $new_user->last_name = $claims['last_name'];
                $new_user->verify_status = $claims['verify_status'];
                $new_user->fake_student = 0;
                $new_user->email = $email;
                $new_user->formative_student = 0;
                $new_user->testing_student = 0;
                $roles = ['instructor' => 2, 'student' => 3];
                $role = $claims['role'];
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
                }
                $new_user->role = $adapt_role;
                $new_user->save();
            }
            $response['type'] = 'success';
            $response['user_created_central_identity_id'] = $claims['central_identity_id'];
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * @param Request $request
     * @return array
     * @throws InvalidSignatureException
     * @throws InvalidTokenException
     * @throws JsonDecodingException
     * @throws SigningException
     * @throws ValidationException
     * @throws Exception
     */
    private function _hasAccess(Request $request): array
    {
        if (!$request->bearerToken()) {
            throw new Exception ('Missing Bearer Token.');
        }
        $token = $request->bearerToken();
        $key = new HmacKey($this->bearer_token);
        $signer = new HS256($key);
        $parser = new Parser($signer);
        return $parser->parse($token);
    }


    public function callback(Request $request)
    {
        try {
            $client = new Client();
            $oidcTokenUrl = $this->tokenUrl;
            // Exchange authorization code for access token
            $response = $client->post($oidcTokenUrl, [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $request->code,
                    'redirect_uri' => $this->callbackUrl,
                    'client_id' => $this->client_id,
                    'client_secret' => $this->client_secret,
                ],
            ]);

            $tokenData = json_decode((string)$response->getBody(), true);

            $this->_verifyAccessToken($tokenData['id_token'], $this->jwksUrl);
            // Get user information
            $profileUrl = $this->profileUrl;
            $profileResponse = $client->get($profileUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $tokenData['access_token'],
                ],
            ]);

            $userData = json_decode((string)$profileResponse->getBody(), true);
            dd($userData);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }

        // Do something with the user information (e.g., create or authenticate the user)
    }


// Function to fetch JWKS
    function _fetchJWKS($jwksUrl)
    {
        $jwks = file_get_contents($jwksUrl);
        return json_decode($jwks, true);
    }

// Function to verify the access token

    /**
     * @param $accessToken
     * @param $jwksUrl
     * @return void
     * @throws Exception
     */
    private function _verifyAccessToken($accessToken, $jwksUrl): void
    {
        // Fetch the JWKS
        $jwks = $this->_fetchJWKS($jwksUrl);

        // Decode the access token
        $tokenParts = explode('.', $accessToken);
        $header = json_decode(base64_decode($tokenParts[0]), true);
        // Check if the key is in the JWKS
        $keys = JWK::parseKeySet($jwks, 'RS256');
        if (!isset($header['kid'])) {
            throw new Exception('No key ID (kid) in the token header.');
        }

        // Get the appropriate key
        if (!isset($keys[$header['kid']])) {
            throw new Exception('Key not found in JWKS.');
        }

        // Verify the token

        try {
            JWT::decode($accessToken, $keys);
            return; // Return the decoded token payload
        } catch (Exception $e) {
            throw new Exception('Token verification failed: ' . $e->getMessage());
        }
    }


}
