<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DataShop extends Model
{

    public $timestamps = false;

    /**
     * @param $submission
     * @param $data
     * @param Assignment $assignment
     * @param $assignment_question
     */
    public function store($submission, $data, Assignment $assignment, $assignment_question)
    {
        $extra_info = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->join('users', 'courses.user_id', '=', 'users.id')
            ->join('assignment_groups','assignments.assignment_group_id','=','assignment_groups.id')
            ->select('users.email', 'users.first_name', 'users.last_name','assignment_groups.assignment_group')
            ->where('assignments.id',$assignment->id)
            ->first();
        $level_points = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->sum('points');
        $question = Question::find($submission->question_id);
        $this->anon_student_id = Auth::user()->email ? Auth::user()->email : 'test';
        $this->session_id = session()->get('submission_id');
        $this->time = Carbon::now();
        $this->level = $assignment->id;
        $this->level_name = $assignment->name;
        $this->level_group = $extra_info->assignment_group;
        $this->level_scoring_type = $assignment->scoring_type;
        $this->level_points = $level_points;
        $this->number_of_attempts_allowed = $assignment->assessment_type === 'delayed' ? 'unlimited' : '1';
        $this->problem_name = $submission->question_id;
        $this->problem_points = $assignment_question->points;
        $this->library = $question->library;
        $this->page_id = $question->page_id;
        $this->url = $question->url;
        $this->problem_view = $submission->submission_count;
        $this->outcome = $data['all_correct'] ? 'CORRECT' : 'INCORRECT';
        $this->due = $assignment->assignToTimingByUser('due');
        $this->school = $assignment->course->school_id;
        $this->class = $assignment->course->id;
        $this->class_name = $assignment->course->name;
        $this->class_start_date = $assignment->course->start_date;
        $this->instructor_name = "$extra_info->first_name $extra_info->last_name";
        $this->instructor_email = $extra_info->email;
        $this->status = 'fixed';

        $this->save();
    }

}
