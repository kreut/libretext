<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\LoginAsRequest;
use App\User;
use App\Course;
use App\Assignment;
use App\Exceptions\Handler;
use \Exception;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get authenticated user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function current(Request $request)
    {
        return response()->json($request->user());
    }

    public function getSession(Request $request)
    {
        return $request->session()->all();
    }

    public function toggleStudentView(Request $request, User $User, Course $Course, Assignment $Assignment)
    {
        $response['type'] = 'error';
        $course_id = $request->course_id;
        $assignment_id = $request->assignment_id;
        $route_name = $request->route_name;
        try {
            if (!$course_id) {
                $course_id = $Assignment->find($assignment_id)->course->id;
            }
            if (!$request->session()->exists('instructor_user_id')) {
                //remember who they are and log them in as fake student
                session(['instructor_user_id' => $request->user()->id]);
                $new_user = $Course->find($course_id)->enrollments->sortBy('id')->first();
                $new_user = $User->where('id', $new_user['user_id'])
                    ->where('first_name', 'Fake')
                    ->where('last_name', 'Student')
                    ->where('email', null)
                    ->first();//don't REALLY need this -- just to double check that I didn't do something silly
                $new_user_types = 'students';

            } else {
                $user_id = session('instructor_user_id');
                $new_user = $User::find($user_id);
                $request->session()->forget('instructor_user_id');
                $new_user_types = 'instructors';

            }

            $response['type'] = 'success';
            $response['new_route_name'] = $this->getNewRouteFromOldRouteAndNewUserType($route_name, $new_user_types);
            $response['token'] = \JWTAuth::fromUser($new_user);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error switching views. Please try again or contact us for assistance.";

        }

        return $response;


    }

    public function getNewRouteFromOldRouteAndNewUserType(string $route_name, string $new_user_types)
    {

        $current_user_types = $new_user_types === 'students' ? 'instructors' : 'students';
        $route_name_without_role = str_replace($current_user_types . '.', '', $route_name);
        if ($route_name === 'questions.view') {
            return 'questions.view';
        } else if (in_array($route_name_without_role, ['assignments.index', 'courses.index', 'assignments.summary'])) {
            return "$new_user_types.$route_name_without_role";
        } else {
            return "$new_user_types.courses.index";
        }


    }

    /**
     * @param Request $request
     * @param User $user
     * @param Course $Course
     * @return array
     * @throws Exception
     */
    public function loginAsStudentInCourse(Request $request, Course $Course)
    {
        $response['type'] = 'error';

        $course = $Course->where('id', $request->course_id)->first();

        $authorized = Gate::inspect('loginAsStudentInCourse', [$course, $request->student_user_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $new_user = User::find($request->student_user_id);
            session(['instructor_user_id' => $request->user()->id]);//to remember who to toggle back to!
            $response['type'] = 'success';
            $response['token'] = \JWTAuth::fromUser($new_user);
            $response['success'] = true;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error logging you in as this student.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param User $user
     * @return array|bool
     */
    public function loginAs(LoginAsRequest $request, User $user)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('loginAs', $user);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $user_info = [];
        try {
            $user_info = explode(' --- ', $request->user);
            $email = $user_info[1];
            $new_user = User::where('email', $email)->first();
            $response['type'] = 'success';
            $response['token'] = \JWTAuth::fromUser($new_user);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error logging in as {$user_info[0]}.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    public function getAll(Request $request, User $user)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getAll', $user);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $response['users'] = DB::table('users')
                ->orderBy('last_name')
                ->select(DB::raw('CONCAT(first_name, " " , last_name, " --- ", email) AS user'))
                ->where('email', '<>', null)
                ->get()
                ->pluck('user');
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting all users.  Please try again or contact us for assistance.";

        }
        return $response;


    }

    public function getAuthenticatedUser(Request $request)

    {
        try {
            $payload = \JWTAuth::parseToken()->getPayload();
            if (!$user = \JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json(['token_expired'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['token_invalid'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['token_absent'], $e->getStatusCode());

        }
        Log::info(\JWTAuth::parseToken()->getPayload() . "\r\n");
        Log::info($request->all());
        // the token is valid and we have found the user via the sub claim
        return [\JWTAuth::parseToken()->getPayload(), $request->all()];
    }
}
