<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolController extends Controller
{
    /**
     * @return array
     * @throws Exception
     */
    public function index(): array
    {

        $response['type'] = 'error';
        try {
            $response['schools'] = DB::table('schools')
                ->groupBy('name')
                ->select('name')
                ->get()
                ->pluck('name');

            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get a list of all schools.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getSchoolsWithPublicCourses(Request $request)
    {

        $response['type'] = 'error';
        try {
            $response['schools'] = DB::table('schools')
                ->join('courses','schools.id', '=','courses.school_id')
                ->where('courses.public',1)
                ->groupBy('schools.name')
                ->select('schools.name')
                ->get()
                ->pluck('name');

            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get a list of all schools with public courses.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
