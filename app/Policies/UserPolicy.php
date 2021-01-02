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

    }

    private function isAdmin($user){
        $admins = ['kreut@hotmail.com', 'adapt@libretexts.org'];
        if ( in_array(env('APP_ENV')  ,['local','testing'])){
            $admins[] = 'me@me.com';
        }

        $isValidEmail =  in_array(session()->get('original_email'),$admins);//get the original email since they may be in student view

        $isValidCookie  =isset(request()->cookie()['IS_ME']) && (request()->cookie()['IS_ME'] === env('IS_ME_COOKIE'));
        return $isValidEmail && $isValidCookie;
    }
    public function getAll(User $user)
    {

        return $this->isAdmin($user)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the users from the database.');
    }

    public
    function loginAs(User $user)
    {

        return$this->isAdmin($user)
            ? Response::allow()
            : Response::deny('You are not allowed to log in as a different user.');
    }

}
