<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\User;
use App\UsersWithNoRole;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UsersWithNoRoleController extends Controller
{
    /**
     * @param Request $request
     * @param UsersWithNoRole $usersWithNoRole
     * @return array
     * @throws Exception
     */
    public function index(Request $request, UsersWithNoRole $usersWithNoRole): array
    {
        $authorized = Gate::inspect('index', $usersWithNoRole);

        $response['type'] = 'error';
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['users_with_no_role'] = DB::table('users')
                ->where('role', 0)
                ->orderBy('created_at', 'DESC')
                ->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the users without roles.  Please refresh your page and try again.";
        }
        return $response;

    }

    public function update(Request $request, User $user, UsersWithNoRole $usersWithNoRole): array
    {
        $user_with_no_role = User::find($user->id);
        $authorized = Gate::inspect('update', [$usersWithNoRole, $user_with_no_role->role]);

        $response['type'] = 'error';
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            switch ($request->role) {
                case('student'):
                    $role = 3;
                    break;
                case('instructor'):
                    $role = 2;
                    break;
                default:
                    $response['message'] ="$request->role is an invalid role.";
                    return $response;
            }
            $user_with_no_role->role = $role;
            $user_with_no_role->time_zone = 'America/Los_Angeles';
            DB::beginTransaction();
            DB::table('users_with_no_role')->where('email', $user->email)->delete();
            $user_with_no_role->save();
            DB::commit();
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the user.  Please refresh your page and try again.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param User $user
     * @param UsersWithNoRole $usersWithNoRole
     * @return array
     * @throws Exception
     */
    public function destroy(Request $request, User $user, UsersWithNoRole $usersWithNoRole): array
    {

        $authorized = Gate::inspect('destroy', [$usersWithNoRole, $user->role]);

        $response['type'] = 'error';
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            DB::beginTransaction();
            DB::table('users_with_no_role')->where('email', $user->email)->delete();
            $user->delete();
            DB::commit();
            $response['message'] = "$user->first_name $user->last_name has been removed from ADAPT.";
            $response['type'] = 'info';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting this user.  Please refresh your page and try again.";
        }
        return $response;

    }
}
