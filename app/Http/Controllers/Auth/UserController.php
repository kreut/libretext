<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Exceptions\Handler;
use \Exception;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    /**
     * @param Request $request
     * @param User $user
     * @return array|bool
     */
    public function loginAs(Request $request, User $user){
        $response['type'] = 'error';

        $authorized = Gate::inspect('loginAs', $user);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $user_info = explode(' --- ', $request->user);
        $email = $user_info[1];
        $new_user = User::where('email',$email)->first();
        $response['type'] = 'success';
        $response['token'] = \JWTAuth::fromUser($new_user);
        return $response;

    }


    public function getAll(Request $request, User $user){

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAll', $user);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $response['users'] = DB::table('users')
                ->orderBy('last_name')
                ->select(DB::raw('CONCAT(first_name, " " , last_name, " --- ", email) AS user'))
                ->where('email', '<>', null)
                ->get()
                ->pluck('user');
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";

        }
        return $response;



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
