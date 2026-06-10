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
     * Check if the user is a co-editor of the given macro.
     */
    private function isCoEditor(User $user, WebworkMacro $webworkMacro): bool
    {
        return DB::table('webwork_macro_co_editors')
            ->where('webwork_macro_id', $webworkMacro->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function store(User $user, WebworkMacro $webworkMacro): Response
    {
        return Helper::isWebworkMacroEditor()
            ? Response::allow()
            : Response::deny("You are not allowed to create macros.");
    }

    public function postToWebworkServer(User $user, WebworkMacro $webworkMacro): Response
    {
        // Allow global editors, owners, and co-editors to push to the WeBWork server
        return (Helper::isWebworkMacroEditor()
            || (int)$webworkMacro->user_id === (int)$user->id
            || $this->isCoEditor($user, $webworkMacro))
            ? Response::allow()
            : Response::deny("You are not allowed to post to the webwork server.");
    }

    public function revisions(User $user, WebworkMacro $webworkMacro): Response
    {
        // Allow global editors, owners, and co-editors to view/compare revisions
        return (Helper::isWebworkMacroEditor()
            || (int)$webworkMacro->user_id === (int)$user->id
            || $this->isCoEditor($user, $webworkMacro))
            ? Response::allow()
            : Response::deny("You are not allowed to view the revisions.");
    }

    public function update(User $user, WebworkMacro $webworkMacro): Response
    {
        // Allow admins, owners, and co-editors to update
        return (Helper::isAdmin()
            || (int)$webworkMacro->user_id === (int)$user->id
            || $this->isCoEditor($user, $webworkMacro))
            ? Response::allow()
            : Response::deny("You are not allowed to edit that macro.");
    }

    public function destroy(User $user, WebworkMacro $webworkMacro): Response
    {
        // Co-editors may NOT retire — only owner or admin
        return (Helper::isAdmin() || (int)$webworkMacro->user_id === (int)$user->id)
            ? Response::allow()
            : Response::deny("You are not allowed to retire that macro.");
    }
}
