<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class LtiSchoolController extends Controller
{
    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function index(Request $request): array
    {

        try {
            $response['type'] = 'error';
            $lti_schools = DB::table('lti_schools')
                ->join('schools', 'lti_schools.school_id', '=', 'schools.id')
                ->join('lti_registrations', 'lti_registrations.id', '=', 'lti_schools.lti_registration_id');
            if ($request->user()->email !== 'adapt@libretexts.org') {
                $lti_schools = $lti_schools->where('active', 1);
            }
            $lti_schools = $lti_schools->get()
                ->pluck('name')
                ->toArray();
            $response['lti_schools'] = $lti_schools;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get the LTI registrations.  Please try again.";
        }
        return $response;

    }
}
