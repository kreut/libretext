<?php

namespace App\Http\Controllers\Auth;

use App\Course;
use App\Enrollment;
use App\Exceptions\Handler;
use App\Exceptions\VerifyEmailException;
use App\FCMToken;
use App\Http\Controllers\Controller;
use App\Question;
use App\User;
use Exception;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }


    /**
     * Attempt to log the user into the application.
     *
     * @param Request $request
     * @return bool
     */
    protected function attemptLogin(Request $request): bool
    {
        $token = $this->guard()->attempt($this->credentials($request));

        if (!$token) {
            return false;
        }

        $user = $this->guard()->user();
        session()->forget('original_user_id');
        session()->forget('admin_user_id');
        session()->put('original_role', $user->role);
        session()->put('original_email', $user->email);
        $user->instructor_user_id = null;
        DB::table('users')->where('instructor_user_id', $user->id)->update(['instructor_user_id' => null]);

        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
            return false;
        }

        $this->guard()->setToken($token);
        return true;
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param Request $request
     * @return JsonResponse
     */
    protected function sendLoginResponse(Request $request)
    {
        $this->clearLoginAttempts($request);

        $token = (string)$this->guard()->getToken();
        $expiration = $this->guard()->getPayload()->get('exp');
        return response()->json([
            'landing_page' => session()->get('landing_page'),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiration - time(),
        ]);
    }

    /**
     * Get the failed login response instance.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $user = $this->guard()->user();
        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
            throw VerifyEmailException::forUser($user);
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * @param Request $request
     * @return Application|RedirectResponse|Redirector
     */
    public function logout(Request $request)
    {
        $request->session()->flush();
        FCMToken::where('user_id', $request->user()->id)->delete();
        $this->guard()->logout();
        $cookie = Cookie::forget('user_jwt');
        return redirect('/')->withCookie($cookie);

    }

    public function destroy(Request    $request,
                            Course     $course,
                            Enrollment $enrollment,
                            Question   $question)
    {

        $response['type'] = 'error';
        try {
            $user = $request->user();
            $non_zero_role = $user->role > 0;
            $has_courses = $course->where('user_id', $user->id)->first();
            $enrolled_in_courses = $enrollment->where('user_id', $user->id)->first();
            $has_questions = $question->where('question_editor_user_id', $user->id)->first();

            if ($non_zero_role || $has_courses || $enrolled_in_courses || $has_questions) {
                $response['message'] = "You are an active user and cannot be removed from the database.";
                return $response;
            }
            if (!app()->environment('testing')) {
                $this->logout($request);
            }
            $email = $user->email;
            DB::beginTransaction();
            DB::table('oauth_providers')->where('provider_user_id', $email)->delete();
            $user->delete();
            DB::commit();
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not delete you from the system. Please try again or contact us for assistance.";
        }
        return $response;

    }


}
