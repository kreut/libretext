<?php

namespace App\Http\Controllers\Auth;

use App\Enrollment;
use App\Http\Requests\LoginAsRequest;
use App\LoginAsUser;
use App\User;
use App\Course;
use App\Assignment;
use App\Exceptions\Handler;
use \Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class UserController extends Controller
{

    /**
     * @param Request $request
     * @param LoginAsUser $loginAsUser
     * @return JsonResponse
     */
    public function current(Request $request, LoginAsUser $loginAsUser): JsonResponse
    {
        if ($request->user()) {
            $request->user()->is_tester_student = DB::table('tester_students')
                ->where('student_user_id', $request->user()->id)
                ->exists();
            if ($request->user()->is_tester_student) {
                $request->user()->email = '';
            }
            $request->user()->is_developer = $request->user()->isDeveloper();
            $request->user()->is_instructor_logged_in_as_student = $request->user()->instructor_user_id;
            $request->user()->logged_in_as_user = DB::table('login_as_users')->where('logged_in_as_user_id', $request->user()->id)->first();

        }
        return response()->json($request->user());
    }

    public function getSession(Request $request)
    {
        return $request->session()->all();
    }

    /**
     * @param Request $request
     * @param User $User
     * @param Assignment $Assignment
     * @return array
     * @throws Exception
     */
    public function toggleStudentView(Request $request, User $User, Assignment $Assignment): array
    {
        $response['type'] = 'error';
        $course_id = $request->course_id;
        $assignment_id = $request->assignment_id;
        $route_name = $request->route_name;
        $current_user = $request->user();

        $authorized = Gate::inspect('toggleStudentView', $User);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            if (!$course_id) {
                $course_id = $Assignment->find($assignment_id)->course->id;
            }

            if (!$current_user->instructor_user_id) {
                //remember who they are and log them in as fake student
                $new_user = Course::find($course_id)->fakeStudent();
                $new_user->instructor_user_id = $current_user->id;
                $new_user->save();
                $new_user_types = 'students';


            } else {
                $user_id = $current_user->instructor_user_id;
                $new_user = $User::find($user_id);
                $current_user->instructor_user_id = null;
                $current_user->save();
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

    /**
     * @param string $route_name
     * @param string $new_user_types
     * @return string
     */
    public function getNewRouteFromOldRouteAndNewUserType(string $route_name, string $new_user_types): string
    {

        $current_user_types = $new_user_types === 'students' ? 'instructors' : 'students';
        $route_name_without_role = str_replace($current_user_types . '.', '', $route_name);
        if ($route_name === 'instructors.assignments.questions') {
            return 'questions.view';
        }
        if ($route_name === 'questions.view') {
            return 'questions.view';
        } else if (in_array($route_name_without_role, ['assignments.index', 'courses.index', 'assignments.summary'])) {
            return "$new_user_types.$route_name_without_role";
        } else {
            return "$new_user_types.courses.index";
        }


    }

    /**
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function loginToAssignmentAsFormativeStudent(Assignment $assignment): array
    {

        $response['type'] = 'error';
        try {
            if (!$assignment->course->formative && !$assignment->formative) {
                $response['message'] = "This is not a formative assignment.";
                return $response;
            }

            $formative_student_user_id = session()->get('formative_student_user_id')
                ? session()->get('formative_student_user_id')
                : null;
            $user = User::find($formative_student_user_id);
            if (!$user) {
                $user = new User();
                $user->first_name = '';
                $user->last_name = '';
                $user->role = 3;
                $user->email = substr(sha1(mt_rand()), 17, 25);
                $user->student_id = '';
                $user->time_zone = 'America/Los_Angeles';
                $user->formative_student = 1;
                $user->save();
                $section = DB::table('sections')
                    ->where('course_id', $assignment->course->id)
                    ->first();
                if (!$section) {
                    $response['message'] = "This assignment needs to be associated with a course with at least one section.";
                    return $response;
                }
                $enrollment = new Enrollment();
                $enrollment->section_id = $section->id;
                $enrollment->course_id = $assignment->course->id;
                $enrollment->user_id = $user->id;
                $enrollment->save();
                session()->put('formative_student_user_id', $user->id);
            }

            $response['type'] = 'success';
            $response['token'] = \JWTAuth::fromUser($user);
            $response['success'] = true;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error logging you in as a formative student.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param Course $Course
     * @return array
     * @throws Exception
     */
    public function loginAsStudentInCourse(Request $request, Course $Course): array
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

            if ($request->user()->role === 2) {
                session(['instructor_user_id' => $request->user()->id]);//to remember who to toggle back to!
            }
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
     * @param LoginAsRequest $request
     * @param User $user
     * @param LoginAsUser $loginAsUser
     * @return array
     * @throws Exception
     */
    public function loginAs(LoginAsRequest $request, User $user, LoginAsUser $loginAsUser): array
    {
        $response['type'] = 'error';
        $user_info = [];

        try {
            $user_info = explode(' --- ', $request->user);

            $email = $user_info[1];
            $new_user = User::where('email', $email)->first();
            $authorized = Gate::inspect('loginAs', [$user, $email]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $loginAsUser->where('original_user_id', $request->user()->id)->delete();
            $loginAsUser->original_user_id = $request->user()->id;
            $loginAsUser->logged_in_as_user_id = $new_user->id;
            $loginAsUser->save();
            $response['type'] = 'success';
            $response['token'] = \JWTAuth::fromUser($new_user);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error logging in as {$user_info[0]}.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param LoginAsRequest $request
     * @param User $user
     * @param LoginAsUser $loginAsUser
     * @return array
     * @throws Exception
     */
    public function exitLoginAs(Request $request, User $user, LoginAsUser $loginAsUser): array
    {
        $response['type'] = 'error';
        $user_info = [];
        try {
            $authorized = Gate::inspect('exitLoginAs', $loginAsUser);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $logged_in_as_user = $loginAsUser->where('logged_in_as_user_id', $request->user()->id)->first();
            $new_user = $user->where('id', $logged_in_as_user->original_user_id)->first();
            DB::beginTransaction();
            $loginAsUser->where('logged_in_as_user_id', $request->user()->id)->delete();
            $response['type'] = 'success';
            $response['token'] = \JWTAuth::fromUser($new_user);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error exiting the login as.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function getAll(Request $request, User $user): array
    {
        $response['type'] = 'error';
        if ($request->user()->id !== 7665) {
            $authorized = Gate::inspect('getAll', $user);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
        }
        try {
            $users
                = DB::table('users')
                ->orderBy('last_name')
                ->select(DB::raw('CONCAT(first_name, " " , last_name, " --- ", email) AS user'))
                ->where('email', '<>', null)
                ->where('users.formative_student', 0)
                ->where('users.testing_student', 0);
            if ($request->user()->id == 7665) {
                $users = $users->where('email', 'LIKE', '%estrellamountain.edu%')
                    ->where('role', 2)
                    ->where('id', '<>', 7665);
            }
            $users = $users->get()
                ->pluck('user');
            $response['users'] = $users;
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
