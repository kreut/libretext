<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AssignmentProperties;
use App\Traits\DateFormatter;

class BetaAssignment extends Model
{
    protected $guarded = [];
    use AssignmentProperties;
    use DateFormatter;


    function addBetaAssignments(Course     $course,
                                BetaCourse $betaCourse,
                                Assignment $assignment,
                                Section    $section,
                                array      $assign_tos,
                                User       $user)
    {
        if ($course->alpha) {
            $beta_assign_tos[0] = $assign_tos[0];
            $beta_assign_tos[0]['groups'] = [];
            $beta_assign_tos[0]['groups'][0]['text'] = 'Everybody';

            $beta_courses = $betaCourse->where('alpha_course_id', $course->id)->get();
            foreach ($beta_courses as $beta_course) {
                $beta_assignment = $assignment->replicate()->fill([
                    'course_id' => $beta_course->id
                ]);
                $beta_assignment->save();
                $beta_assign_tos[0]['groups'][0]['value']['course_id'] = $beta_course->id;
                BetaAssignment::create([
                    'id' => $beta_assignment->id,
                    'alpha_assignment_id' => $assignment->id
                ]);
                $this->addAssignTos($beta_assignment, $beta_assign_tos, $section, $user);
            }
        }
    }
}
