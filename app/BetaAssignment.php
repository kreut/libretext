<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AssignmentProperties;
use App\Traits\DateFormatter;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Laravel\Facades\Telegram;

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
                //make sure that the assignment group exists

                $assignment_group = DB::table('assignment_groups')
                    ->where('id', $beta_assignment->assignment_group_id)
                    ->first();
                //if it's a custom assignment group, we'll need to either create a new assignment group or
                if ($assignment_group->user_id) {
                    $beta_course_info = DB::table('courses')
                        ->where('id',$beta_course->id)
                        ->first();
                    $beta_course_assignment_group = DB::table('assignment_groups')
                        ->where('assignment_group', $assignment_group->assignment_group)
                        ->where('course_id', $beta_course_info->id)
                        ->first();
                    if (!$beta_course_assignment_group){
                        $beta_course_assignment_group = AssignmentGroup::create([
                            'assignment_group' => $assignment_group->assignment_group,
                            'user_id' => $beta_course_info->user_id,
                            'course_id' => $beta_course_info->id]);
                    }
                    $beta_assignment->assignment_group_id = $beta_course_assignment_group->id;
                    $beta_assignment->save();
                }

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
