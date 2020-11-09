<?php

namespace App\Http\Controllers;

use App\AssignmentGroupWeight;
use App\AssignmentGroup;
use App\Course;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
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
        $assignment_group_weights = DB::table('assignments')
            ->join('assignment_groups', 'assignments.assignment_group_id', '=', 'assignment_groups.id')
            ->leftJoin('assignment_group_weights', 'assignment_groups.id', '=', 'assignment_group_weights.assignment_group_id')
            ->where('assignments.course_id', $course->id)
            ->groupBy('assignment_groups.id','assignment_group_weights.assignment_group_weight')
            ->select('assignment_groups.id','assignment_groups.assignment_group','assignment_group_weights.assignment_group_weight')
            ->get()
        ;


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

        dd($request->all());
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$assignmentGroupWeight, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {

$sum = 0;
$rules = [];
dd($request->all());

//make sure each is non-empty
            //make sure each is between 0 and 100
            //make sure they sum to 100


            foreach ($request->all() as $id => $percent){
                $validator = Validator::make($request->all(), );

                if ($validator->fails()) {
                    return redirect('post/create')
                        ->withErrors($validator)
                        ->withInput();
                }

            }

           $data = Validator::make($request->all(), [
                'title' => 'required|unique:posts|max:255',
                'body' => 'required',
            ])->validate();

            $response['type'] = 'success';
            $response['message'] = "The assignment group weights have been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the weights.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
