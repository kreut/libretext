<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get authenticated user.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function current(Request $request)
    {
        return response()->json($request->user());
    }

    public function getAuthenticatedUser(Request $request)

    {
        try {
            $payload = \JWTAuth::parseToken()->getPayload();
                 if (! $user = \JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json(['token_expired'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['token_invalid'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['token_absent'], $e->getStatusCode());

        }
        Log::info( \JWTAuth::parseToken()->getPayload() . "\r\n" );
        Log::info( $request->all() );
        // the token is valid and we have found the user via the sub claim
        return [ \JWTAuth::parseToken()->getPayload() , $request->all()] ;
    }
}
