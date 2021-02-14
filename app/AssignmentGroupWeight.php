<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AssignmentGroupWeight extends Model
{
    protected $guarded = [];

    /**
     * @param Course $from_course
     * @param Course $to_course
     * @param int $from_assignment_group_id
     * @param bool $set_weight_to_zero
     */
    public function importAssignmentGroupWeightToCourse(Course $from_course,
                                                        Course $to_course,
                                                        int $imported_assignment_group_id,
                                                        bool $set_weight_to_zero)
    {
        $from_assignment_group_weight = $this->where('course_id', $from_course->id)
            ->where('assignment_group_id', $imported_assignment_group_id)
            ->first();
        $assignment_group_weight = $set_weight_to_zero ? 0 : $from_assignment_group_weight->assignment_group_weight;
        $this->firstOrCreate(['assignment_group_id' => $imported_assignment_group_id,
            'course_id' => $to_course->id],
            ['assignment_group_weight' =>  $assignment_group_weight]);


    }

    public function validateCourseWeights(Course $course)
    {
        $response['type'] = 'success';
        $total_weights = 0;
        foreach ($course->assignmentGroupWeights() as $key => $value) {
            $total_weights += $value->assignment_group_weight;
        }
        if ($total_weights !== 100) {
            $response['message'] = "Please first update your Assignment Group Weights so that the total weighting is equal to 100.";
            $response['type'] = 'error';
        }
        return $response;
    }
}
