<?php

namespace App\Http\Controllers;

use App\AssignmentGroupWeight;
use App\Course;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

use App\Exceptions\Handler;
use \Exception;


class AssignmentGroupWeightController extends Controller
{

    public function index(Request $request, Course $course, AssignmentGroupWeight $assignmentGroupWeight)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentGroupWeights', [$assignmentGroupWeight, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $assignment_group_weights = $course->assignmentGroupWeights();

        try {
            $response['assignment_group_weights'] = $assignment_group_weights;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the weights.  Please try again or contact us for assistance.";
        }
        return $response;


    }


    public function update(Request $request, Course $course, AssignmentGroupWeight $assignmentGroupWeight)
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$assignmentGroupWeight, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $this->validateAssignmentGroupWeights($request, $course);

            foreach ($request->all() as $id => $weight) {
                AssignmentGroupWeight::updateOrCreate(
                    ['course_id' => $course->id, 'assignment_group_id' => $id],
                    ['assignment_group_weight' => $weight]
                );
            }

            $response['type'] = 'success';
            $response['message'] = "The assignment group weights have been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the weights.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function validateAssignmentGroupWeights(Request $request, Course $course)
    {
        $sum = 0;
        $response['form_error'] = true;
        $extra_credit_id  = 0;

        foreach ($course->assignmentGroupWeights() as $key=>$value){
            if ($value->assignment_group === 'Extra Credit'){
                $extra_credit_id = $value->id;
            }
        }

        if (count($course->assignmentGroupWeights()) !== count($request->all())) {
            $response['message'] = 'Every percentage weight should have a value.';
            echo json_encode($response);
            exit;

        }

        foreach ($request->all() as $key => $value) {

            if (!is_numeric($value)) {
                $response['message'] = 'Every percentage weight should be a number.';
                echo json_encode($response);
                exit;

            }
            if ($value < 0 || $value > 100) {
                $response['message'] = 'Every percentage weight should be between 0 and 100.';
                echo json_encode($response);
                exit;

            }
            if ($key !==  $extra_credit_id ) {
                $sum += $value;
            }
        }
        if ($sum !== 100) {
            $response['message'] = 'The non-extra credit percentage weights should sum to 100.';
            echo json_encode($response);
            exit;
        }
    }

}
