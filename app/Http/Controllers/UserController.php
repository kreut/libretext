<?php

namespace App\Http\Controllers;


use App\Exceptions\Handler;
use App\School;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function getInstructorsWithPublicCourses(Request $request, User $user, School $school)
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
