<?php

namespace App\Policies;

use App\Helpers\Helper;
use App\User;
use App\WebworkMacro;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class WebworkMacroPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param WebworkMacro $webworkMacro
     * @return Response
     */
    public function store(User $user, WebworkMacro $webworkMacro): Response
    {
        return Helper::isWebworkMacroEditor()
            ? Response::allow()
            : Response::deny("You are not allowed to create macros.");
    }

    /**
     * @param User $user
     * @param WebworkMacro $webworkMacro
     * @return Response
     */
    public function postToWebworkServer(User $user, WebworkMacro $webworkMacro): Response
    {
        return Helper::isWebworkMacroEditor()
            ? Response::allow()
            : Response::deny("You are not allowed to post to the webwork server.");
    }

    /**
     * @param User $user
     * @param WebworkMacro $webworkMacro
     * @return Response
     */
    public function revisions(User $user, WebworkMacro $webworkMacro): Response
    {
        return Helper::isWebworkMacroEditor()
            ? Response::allow()
            : Response::deny("You are not allowed to view the revisions.");
    }

    /**
     * @param User $user
     * @param WebworkMacro $webworkMacro
     * @return Response
     */
    public function update(User $user, WebworkMacro $webworkMacro): Response
    {
        return (Helper::isAdmin() || (int)$webworkMacro->user_id === (int)$user->id)
            ? Response::allow()
            : Response::deny("You are not allowed to edit that macro.");
    }

    public function destroy(User $user, WebworkMacro $webworkMacro): Response
    {
        return (Helper::isAdmin() || (int)$webworkMacro->user_id === (int)$user->id)
            ? Response::allow()
            : Response::deny("You are not allowed to retire that macro.");
    }

}
