<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    private $admins;

    public function __construct()
    {
        $this->admins = ['me@me.com', 'kreut@hotmail.com', 'adapt@libretexts.org'];
    }

    public function getAll(User $user)
    {

        return (in_array($user->email, $this->admins))
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the users from the database.');
    }

    public
    function loginAs(User $user)
    {

        return (in_array($user->email, $this->admins))
            ? Response::allow()
            : Response::deny('You are not allowed to log in as a different user.');
    }

}
