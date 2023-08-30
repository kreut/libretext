<?php

namespace App\Policies;

use App\LibreOneAccessCode;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Request;

class LibreOneAccessCodePolicy
{
    use HandlesAuthorization;

    /**
     * @param Request $request
     * @return bool
     */
    private function _validBearerToken(Request $request): bool
    {
        return $request->bearerToken() && $request->bearerToken() === config('myconfig.libre_one_token');
    }

    public function test(User $user, LibreOneAccessCode $libreOneAccessCode, Request $request): Response
    {
dd("sdfsd");
        $has_access = $this->_validBearerToken($request);
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed get users by access codes.');

    }
}
