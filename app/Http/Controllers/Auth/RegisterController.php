<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\IsNotOauthProviderUserId;
use App\Rules\IsValidQuestionEditorAccessCode;
use App\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Rules\IsValidInstructorAccessCode;
use App\Rules\IsValidGraderAccessCode;
use App\Rules\IsValidTimeZone;

use App\Exceptions\Handler;
use \Exception;

use App\Traits\Registration;

class RegisterController extends Controller
{
    use RegistersUsers;
    use Registration;

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
            'email' => ['required','email','max:255','unique:users', new IsNotOauthProviderUserId()],
            'password' => 'required|min:6|confirmed',
        ];
        switch ($data['registration_type']) {
            case('instructor'):
                $validator['access_code'] = new IsValidInstructorAccessCode();
                break;
            case('grader'):
                $validator['access_code'] = new IsValidGraderAccessCode();
                break;
            case('student'):
                $validator['student_id'] = 'required';
                break;
            case('question editor'):
                $validator['access_code'] = new IsValidQuestionEditorAccessCode();

        }
        $validator['time_zone'] = new IsValidTimeZone();

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

        try {
            DB::beginTransaction();
            [$section_ids, $role] = $this->setRole($data);
            $user = new User;
            $user->first_name = $data['first_name'];
            $user->last_name = $data['last_name'];
            $user->email = $data['email'];
            $user->student_id = $data['student_id'] ?? null;

            $user->password = bcrypt($data['password']);
            $user->time_zone = $data['time_zone'];
            $user->role = $role;
            $user->save();
            if ($role === 4) {
                $this->addGraderToCourse($user->id, $section_ids);
            }

            DB::commit();

            return $user;

        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error completing your registration.  Please try again or contact us for assistance.";
        }

        return $response;
    }
}
