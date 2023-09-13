<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\PassbackByAssignment;
use Exception;
use Illuminate\Support\Facades\Gate;

class PassbackByAssignmentController extends Controller
{
    /**
     * @param Assignment $assignment
     * @param PassbackByAssignment $passbackByAssignment
     * @return array
     * @throws Exception
     */
    public function store(Assignment           $assignment,
                          PassbackByAssignment $passbackByAssignment): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('store', [$passbackByAssignment, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $passbackByAssignment = new PassbackByAssignment();
            $passbackByAssignment->assignment_id = $assignment->id;
            $passbackByAssignment->status = 'pending';
            $passbackByAssignment->save();
            $assignment_name = Assignment::find($assignment->id)->name;
            $response['message'] = "Grades for <strong>$assignment_name</strong> will start to be passed back to your LMS within a minute.  For large classes, the full process may take a few minutes to complete.";
            $response['type'] = 'success';
            return $response;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to passback the grades for this assignment.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
