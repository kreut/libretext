<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AssignmentGroupWeight extends Model
{
    protected $guarded = [];

    public function validateCourseWeights(Course $course){
        $total_weights = 0;
        foreach ($course->assignmentGroupWeights() as $key =>$value){
            $total_weights += $value->assignment_group_weight;
        }
        if ($total_weights !== 100){
            $response['message'] = "Please first update your Assignment Group Weights so that the total weighting is equal to 100.";
            $response['type'] = 'error';
            echo json_encode($response);
            exit;
        }
    }
}
