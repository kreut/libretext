<?php

namespace App\Http\Controllers;

use App\AnalyticsDashboard;
use App\Course;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AnalyticsDashboardController extends Controller
{
    /**
     * @param Course $course
     * @param AnalyticsDashboard $analyticsDashboard
     * @return array
     * @throws Exception
     */
    public function show(Course $course, AnalyticsDashboard $analyticsDashboard): array
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('show', [$analyticsDashboard, $course]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $analytics_dashboard = DB::table('analytics_dashboards')
                ->where('course_id', $course->id)
                ->first();
            if (!$analytics_dashboard) {
                $analyticsDashboard->course_id = $course->id;
                $analyticsDashboard->analytics_course_id = 0;
                $analyticsDashboard->shared_key = uniqid(true);
                $analyticsDashboard->authorized = 0;
                $analyticsDashboard->save();
                $analytics_dashboard = $analyticsDashboard;
            }
            $response['analytics_course_id'] = $analytics_dashboard->analytics_course_id;
            $response['shared_key'] = $analytics_dashboard->shared_key;
            $response['authorized'] = $analytics_dashboard->authorized;
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We are unable to retrieve the analytics dashboard for this course.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param string $analytics_course_id
     * @param AnalyticsDashboard $analyticsDashboard
     * @return array
     * @throws Exception
     */
    public function unsync(Request $request, string $analytics_course_id, AnalyticsDashboard $analyticsDashboard): array
    {

        $response['type'] = 'error';
        if (!($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_dashboard_token'))){
            $response['message'] = "Not authorized.";
            return $response;
        }
        $analytics_dashboard =  $analyticsDashboard
            ->where('analytics_course_id', $analytics_course_id)
            ->first();
        if (!$analytics_dashboard) {
            $response['type'] = 'error';
            $response['message'] = "The analytics course ID $analytics_course_id does not exist.";
            return $response;
        }
        try {
            $analytics_dashboard->delete();
            $response['message'] = "Analytics course $analytics_course_id has been deleted.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We are unable to unsync your account with ADAPT.";

        }
        return $response;
    }

    /**
     * @param Request $request
     * @param string $analytics_course_id
     * @return array
     * @throws Exception
     */
    public function sync(Request $request, string $analytics_course_id): array
    {

        $shared_key = $request->bearerToken();
        $response['type'] = 'error';
        if (!$shared_key) {
            $response['message'] = "Missing a shared key in the request.";
            return $response;
        }
        $analytics_dashboard = DB::table('analytics_dashboards')->where('shared_key', $shared_key)->first();
        if (!$analytics_dashboard) {
            $response['message'] = "The shared key $shared_key does not exist.";
            return $response;
        }
        try {
            DB::table('analytics_dashboards')
                ->where('shared_key', $shared_key)
                ->update(['shared_key' => '',
                    'authorized' => 1,
                    'analytics_course_id' => $analytics_course_id]);
            $response['course_id'] = $analytics_dashboard->course_id;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We are unable to sync your account with ADAPT.";

        }
        return $response;
    }

}
