<?php

namespace App\Http\Controllers;

use App\CourseOrder;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CourseOrderController extends Controller
{
    /**
     * @param Request $request
     * @param CourseOrder $courseOrder
     * @return array
     * @throws Exception
     */
    public
    function order(Request $request, CourseOrder $courseOrder): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('order', [$courseOrder, $request->ordered_courses]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            DB::beginTransaction();
            $courseOrder->orderCourses($request->user(), $request->ordered_courses);
            DB::commit();
            $response['message'] = 'Your courses have been re-ordered.';
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error re-ordering your courses.  Please try again or contact us for assistance.";
        }
        return $response;


    }

}
