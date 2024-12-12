<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\OIDC;
use App\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Exceptions\InvalidSignatureException;
use MiladRahimi\Jwt\Exceptions\InvalidTokenException;
use MiladRahimi\Jwt\Exceptions\JsonDecodingException;
use MiladRahimi\Jwt\Exceptions\SigningException;
use MiladRahimi\Jwt\Exceptions\ValidationException;
use MiladRahimi\Jwt\Parser;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Telegram\Bot\Laravel\Facades\Telegram;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

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

        $this->callbackUrl = url('api/oidc/libreone/callback');
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

    /**
     * @param Request $request
     * @param string $mode
     * @return Application|RedirectResponse|Redirector
     */
    public function initiateLogin(Request $request, string $mode)
    {

        $data = [
            'mode' => $mode,
            'state' => substr(sha1(mt_rand()), 17, 12),
            'orgID' => 'ADAPT (' . app()->environment() . ')',
            'redirectURI' => $request->redirect_url ? $request->redirect_url : '',
            'clicker_app' => $mode === 'app'
        ];
        $nonce = (string)Str::uuid();

        $nonce_hash = Hash::make($nonce);


        $oidcAuth = $this->authorizeUrl;
        $state = json_encode($data);
        $base64State = base64_encode($state);


        $cookie = cookie('oidc_state', $base64State, 1, null, null, true, true, false, 'None');
        $cookie_2 = cookie('oidc_nonce', $nonce, 1, null, null, true, true, false, 'None');

        $params = [
            'client_id' => $this->client_id,
            'response_type' => 'code',
            'scope' => 'openid profile email libretexts',
            'redirect_uri' => $this->callbackUrl,
            'state' => $state,
            'nonce' => $nonce_hash
        ];

        return redirect($oidcAuth . '?' . http_build_query($params))->withCookies([$cookie, $cookie_2]);


    }

    public function changeEmail(OIDC $OIDC)
    {
        $central_identity_id = "bd42a7db-a35a-47e7-8da2-e6aedafcc952";
        if (app()->environment('local')) {
            dd($OIDC->changeEmail($central_identity_id, "newemail@hotmail.com"));
        } else {
            dd("Only can be run from local.");
        }
    }

    public function autoProvision(OIDC $OIDC)
    {
        if (app()->environment('local')) {
            $data = [
                'email' => 'some-sillystudents@hotmail.com',
                'first_name' => 'Test',
                'last_name' => 'Student',
                'user_type' => 'student',
                'time_zone' => 'America/Los_Angeles',
            ];
            dd($OIDC->autoProvision($data));
        } else {
            dd("Only can be run from local.");
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
                $roles = ['instructor' => 2,
                    'student' => 3,
                    'grader' => 4,
                    'question-editor' => 5,
                    'tester' => 6];
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
                $new_user->role = $adapt_role;
                $new_user->save();
            }
            $response['type'] = 'success';
            $response['user_created_central_identity_id'] = $claims['central_identity_id'];
            $response['schema_and_host'] = request()->getSchemeAndHttpHost();
            if (isset($claims['source']) && $claims['source'] === 'adapt-registration') {
                $response['token'] = \JWTAuth::fromUser($new_user);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * @param string $token
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function loginByJWT(string $token): array
    {
        try {
            $landing_page = session()->has('landing_page') ? session()->get('landing_page') : '';
            session()->forget('landing_page');
            $response['type'] = 'error';
            if (\JWTAuth::setToken($token)->check()) {
                $user = \JWTAuth::parseToken()->authenticate();
                if (!$user) {
                    throw new Exception ("No user with that JWT exists");
                }
                $response['token'] = \JWTAuth::fromUser($user);
                $response['success'] = 'error';
                $response['landing_page'] = $landing_page;
            } else {
                throw new Exception('Token is invalid.');
            }
            $response['type'] = 'success';
        } catch (TokenExpiredException $e) {
            throw new Exception('Token has expired.');
        } catch (TokenInvalidException $e) {
            throw new Exception('Token is invalid.');
        } catch (JWTException $e) {
            throw new Exception ('Token is absent.');
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

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function callback(Request $request)
    {
        try {
            $response['type'] = 'error';
            $oidc_state = request()->cookie('oidc_state');

            $state_query = $request->state;

            // Decode the base64-encoded state cookie and parse the JSON


            $state = json_decode($state_query, true);

            $state_cookie = json_decode(base64_decode($oidc_state), true); // Decode base64 and parse JSON
// Check if state or state_cookie is invalid, or if the states do not match
            if (!$state || !$state_cookie || ($state['state'] !== $state_cookie['state'])) {
                return response()->json([
                    'err' => true,
                    'err_msg' => 'Bad state or nonce value',
                ], 400);
            }

            $encoded = $this->client_id . ':' . $this->client_secret;//actually don't encode these! encoding broke this piece
            $authVal = base64_encode($encoded);

// Make the POST request with the authorization header and parameters
            $http_response = Http::withHeaders([
                'Authorization' => 'Basic ' . $authVal
            ])->asForm()->post($this->tokenUrl, [
                'grant_type' => 'authorization_code',
                'code' => request()->query('code'),
                'redirect_uri' => $this->callbackUrl
            ]);
            if ($http_response->failed()) {
                throw new Exception($http_response->body());
            }
            $data = $http_response->json();
            $access_token = $data['access_token'] ?? null;
            $id_token = $data['id_token'] ?? null;

            if (!$access_token || !$id_token) {
                throw new Exception("No tokens are present.");
            }

            $payload = $this->_verifyAccessToken($id_token, $this->jwksUrl);

            $oidc_nonce = request()->cookie('oidc_nonce');
            $nonce = $payload->nonce;

            if (!$nonce || !$oidc_nonce) {
                return response()->json([
                    'err' => true,
                    'errMsg' => "Non-hashed nonce does not match."
                ], 400);
            }

// Verify the nonce values match
            $nonceValid = Hash::check($oidc_nonce, $nonce);

            if (!$nonceValid) {
                return response()->json([
                    'err' => true,
                    'errMsg' => 'Nonce values do not match'
                ], 400);
            }


            // Get user information
            $profileUrl = $this->profileUrl . "?access_token=$access_token";
            $profileResponse = Http::get($profileUrl);

            $user_data = json_decode((string)$profileResponse->getBody(), true);

            $central_identity_id = $user_data['id'];
            $email = $user_data['attributes']['email'];
            $user = User::where('central_identity_id', $central_identity_id)->first();
            if (!$user) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->central_identity_id = $central_identity_id;
                    $user->save();
                } else {
                    throw new Exception("You have not registered with ADAPT.");
                }
            } else {
                //update email;
                $user->email = $email;
                $user->save();
            }

            $linked_accounts = Helper::getLinkedAccounts($user->id);
            session()->put('linked_accounts', $linked_accounts);
            session()->forget('original_user_id');
            session()->forget('admin_user_id');
            session()->put('original_role', $user->role);
            session()->put('original_email', $user->email);
            $user->linked_accounts = $linked_accounts;
            $user->instructor_user_id = null;

            DB::table('users')->where('instructor_user_id', $user->id)->update(['instructor_user_id' => null]);
            $token = \JWTAuth::fromUser($user);
            $clicker_app = isset($state['clicker_app']) && $state['clicker_app'];
            $cookie = $clicker_app
                ? Cookie::make('clicker_app', 1)
                : Cookie::forget('clicker_app');

            if ($clicker_app) {
                return redirect()->to("/launch-clicker-app/$token/0")->withCookie($cookie);
            } else {

                if (isset($state['redirectURI']) && $state['redirectURI']) {
                    session()->put('landing_page', $state['redirectURI']);
                } else {
                    session()->put('landing_page', '');
                }
                return redirect()->to("/login-by-jwt/$token");
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }
        return $response;

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
     * @return \stdClass
     * @throws Exception
     */
    private function _verifyAccessToken($accessToken, $jwksUrl): \stdClass
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
            return JWT::decode($accessToken, $keys);
        } catch (Exception $e) {
            throw new Exception('Token verification failed: ' . $e->getMessage());
        }
    }


}
