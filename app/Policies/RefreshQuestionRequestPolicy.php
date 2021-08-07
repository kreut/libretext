<?php

namespace App\Policies;

use App\Helpers\Helper;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class RefreshQuestionRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    public function makeRefreshQuestionRequest(User $user)
    {
        return ($user->role === 2)
            ? Response::allow()
            : Response::deny("You are not allowed to make refresh question requests.");

    }

    public function denyRefreshQuestion(User $user){
        return Helper::IsAdmin()
            ? Response::allow()
            : Response::deny("You are not allowed to deny refresh question requests.");

    }

    public function index(User $user){
        return Helper::IsAdmin()
            ? Response::allow()
            : Response::deny("You are not allowed to get the refresh question requests.");

    }
}
