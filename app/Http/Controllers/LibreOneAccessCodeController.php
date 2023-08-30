<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\LibreOneAccessCode;
use App\Libretext;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LibreOneAccessCodeController extends Controller
{
    private function _validBearerToken(Request $request): bool
    {
        return $request->bearerToken() && $request->bearerToken() === config('myconfig.libre_one_token');
    }
    /**
     * @param Request $request
     * @param string $access_code
     * @param LibreOneAccessCode $libreOneAccessCode
     * @return array
     * @throws Exception
     */
    public function getUserByAccessCode(Request $request,
                                        string $access_code,
                                        LibreOneAccessCode $libreOneAccessCode): array
    {
        $response['type'] = 'error';
        try {
            if (!$this->_validBearerToken($request)){
                $response['message'] = "You are not allowed to get the user by access code.";
                return $response;
            }
            $user = $libreOneAccessCode->where('access_code', $access_code)->first();
            if (!$user){
                $response['message'] = "There is no user with the access code $request->access_code";
                return $response;
            }
            $response['user'] = $libreOneAccessCode->where('access_code', $access_code)->first();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the user by the access code.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
