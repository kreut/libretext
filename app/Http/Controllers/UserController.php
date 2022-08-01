<?php

namespace App\Http\Controllers;


use App\Course;
use App\Enrollment;
use App\Exceptions\Handler;
use App\School;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{


    public function getAllQuestionEditors(User $user): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAllQuestionEditors', $user);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $response['question_editors'] = DB::table('users')
                ->select('users.id AS value', DB::raw('CONCAT(first_name, " " , last_name) AS label'))
                ->orderBy('label')
                ->whereIn('role', [2, 5])
                ->where('id', '<>', $user->id)
                ->get();

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'We could not get all the potential question owners.';
        }
        return $response;


    }

    /**
     * @param User $user
     * @return array
     * @throws Exception
     */
    public
    function setAnonymousUserSession(User $user): array
    {
        $response['type'] = 'error';


        $authorized = Gate::inspect('setAnonymousUserSession', $user);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            session()->put('anonymous_user', true);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not set you as an anonymous user.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    /**
     * @param Request $request
     * @param User $user
     * @param School $school
     * @return array
     * @throws Exception
     */
    public
    function getInstructorsWithPublicCourses(Request $request, User $user, School $school): array
    {

        $school_id = $request->name
            ? $school->where('name', $request->name)
                ->first()
                ->id
            : 0;

        try {
            $instructors = DB::table('users')
                ->join('courses', 'users.id', '=', 'courses.user_id')
                ->where('users.role', 2)
                ->where('public', 1);

            if ($school_id) {
                $instructors = $instructors->where('courses.school_id', $school_id);
            }
            $instructors = $instructors->groupBy('users.id')
                ->orderBy('users.last_name')
                ->select('user_id', DB::raw("CONCAT(first_name, ' ',last_name) AS name"))
                ->get();

            $response['type'] = 'success';
            $response['instructors'] = $instructors;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the instructors.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
