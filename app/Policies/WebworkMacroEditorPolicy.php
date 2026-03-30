<?php

namespace App\Policies;

use App\User;
use App\WebworkMacroEditor;
use App\Helpers\Helper;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class WebworkMacroEditorPolicy
{
    use HandlesAuthorization;

    public function index(User $user): Response
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('Only administrators can manage macro editors.');
    }

    public function store(User $user): Response
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('Only administrators can grant the macro editor role.');
    }

    public function destroy(User $user, WebworkMacroEditor $webworkMacroEditor): Response
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('Only administrators can revoke the macro editor role.');
    }
}
