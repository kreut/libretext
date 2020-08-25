<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use App\Role;
use App\InstructorAccessCode;
use App\TaAccessCode;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Rules\IsValidInstructorAccessCode;
use App\Rules\IsValidTaAccessCode;


class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * The user has been registered.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     * @return \Illuminate\Http\JsonResponse
     */
    protected function registered(Request $request, User $user)
    {
        if ($user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();

            return response()->json(['status' => trans('verification.sent')]);
        }

        return response()->json($user);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $validator = [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ];

        if ($data['registration_type'] === 'instructor') {
            $validator['access_code'] = new IsValidInstructorAccessCode();
        }

        if ($data['registration_type'] === 'ta') {
            $validator['access_code'] = new IsValidTaAccessCode();
        }
        return Validator::make($data, $validator);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return \App\User
     */
    protected function create(array $data)
    {


        switch ($data['registration_type']){
            case('student'):
                $role = 3;
                break;
            case('instructor'):
                InstructorAccessCode::where('access_code', $data['access_code'])->delete();
                $role = 2;
                break;
            case('ta'):
                TaAccessCode::where('access_code', $data['access_code'])->delete();
                $role = 4;
                break;
            default:
             return false;
        }

        $user = new User;
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->email = $data['email'];
        $user->password = bcrypt($data['password']);
        $user->role = $role;
        $user->save();

        $response['type'] = 'success';
        $response['user'] = $user;

        return $user;
    }
}
