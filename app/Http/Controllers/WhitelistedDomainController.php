<?php

namespace App\Http\Controllers;

use App\Course;
use App\Exceptions\Handler;
use App\WhitelistedDomain;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WhitelistedDomainController extends Controller
{
    /**
     * @param Request $request
     * @param Course $course
     * @param WhitelistedDomain $whitelistedDomain
     * @return array
     * @throws Exception
     */
    public function store(Request           $request,
                          Course            $course,
                          WhitelistedDomain $whitelistedDomain): array
    {
        try {
            $whitelisted_domain = trim($request->whitelisted_domain);
            $authorized = Gate::inspect('store', [$whitelistedDomain, $course]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $whitelisted_domain_exists = $whitelistedDomain->where('course_id', $course->id)
                ->where('whitelisted_domain', $whitelisted_domain)
                ->first();
            if ($whitelisted_domain_exists) {
                $response['type'] = 'error';
                $response['message'] = "$whitelisted_domain already exists for this course.";
                return $response;
            }
            $whitelistedDomain->whitelisted_domain = $whitelisted_domain;
            $whitelistedDomain->course_id = $course->id;
            $whitelistedDomain->save();
            $response['id'] = $whitelistedDomain->id;
            $response['type'] = 'success';
            $response['message'] = "The whitelisted domain has been added to the course.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error storing the whitelisted domain for this course.  Please try again or contact us for assistance";
        }
        return $response;

    }

    /**
     * @param Course $course
     * @param WhitelistedDomain $whitelistedDomain
     * @return array
     * @throws Exception
     */
    public function getByCourse(Course $course, WhitelistedDomain $whitelistedDomain): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('getByCourse', [$whitelistedDomain, $course]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $response['whitelisted_domains'] = $whitelistedDomain->where('course_id', $course->id)->get();
            $response['type'] = 'success';
            return $response;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the whitelisted domain for this course.  Please try again or contact us for assistance";
        }
        return $response;
    }

    /**
     * @param WhitelistedDomain $whitelistedDomain
     * @return array
     * @throws Exception
     */
    public function destroy(WhitelistedDomain $whitelistedDomain): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('destroy', $whitelistedDomain);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            if (DB::table('whitelisted_domains')->where('course_id',$whitelistedDomain->course_id)->count() === 1){
                $response['message'] = "You need at least one whitelisted domain.";
                return $response;
            }
            $whitelisted_domain = $whitelistedDomain->whitelisted_domain;
            $whitelistedDomain->delete();
            $response['type'] = 'info';
            $response['message'] = "$whitelisted_domain has been removed.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the whitelisted domain for this course.  Please try again or contact us for assistance";
        }
        return $response;



    }
}
