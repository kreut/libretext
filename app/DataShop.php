<?php

namespace App;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DataShop extends Model
{

    public $timestamps = false;

    /**
     * @param string $type
     * @param $data
     * @param Assignment $assignment
     * @param $assignment_question
     * @throws Exception
     */
    public function store(string $type, $data, Assignment $assignment, $assignment_question)
    {
        $extra_info = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->join('users', 'courses.user_id', '=', 'users.id')
            ->join('assignment_groups', 'assignments.assignment_group_id', '=', 'assignment_groups.id')
            ->select('users.email',
                'users.first_name',
                'users.last_name',
                'assignment_groups.assignment_group',
                'courses.textbook_url')
            ->where('assignments.id', $assignment->id)
            ->first();
        $assignment_points = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->sum('points');
        switch ($type) {
            case('submission'):
                $question = Question::find($data['submission']->question_id);
                $this->submission_time = Carbon::now();
                $this->submission_count = $data['submission']->submission_count;
                $this->outcome = $data['all_correct'] ? 'CORRECT' : 'INCORRECT';
                $this->session_id = session()->get('submission_id');
                $this->anon_student_id = Auth::user()->email ? Auth::user()->email : 'test';
                break;
            case('time_to_review'):
                $question = Question::find($data->question_id);
                $this->review_time_start = $data->created_at;
                $this->review_time_end = $data->updated_at;
                $this->session_id = $data->session_id;
                $this->anon_student_id = $data->email;
                break;
            default:
                throw new Exception ("$type is not a valid data_shop type.");

        }


        $this->assignment_id = $assignment->id;
        $this->assignment_name = $assignment->name;
        $this->assignment_group = $extra_info->assignment_group;
        $this->assignment_scoring_type = $assignment->scoring_type;
        $this->assignment_points = $assignment_points;
        $this->number_of_attempts_allowed = $assignment->assessment_type === 'delayed' ? 'unlimited' : '1';
        $this->question_id = $question->id;
        if ($type === 'submission') {
            $this->question_id .= $data['sub_content_id'] ? "-{$data['sub_content_id']}" : '';
        }
        if (!$assignment_question){
            throw new Exception ("Datashop has no assignment-question for $assignment->id with $question->id submitted by $this->anon_student_id.");
        }
        $this->question_points = $assignment_question->points;
        $this->library = $question->library;
        $this->page_id = $question->page_id;
        $this->question_url = $question->url;
        $this->textbook_url = $extra_info->textbook_url;
        $this->due = $assignment->assignToTimingByUser('due');
        $this->school = $assignment->course->school_id;
        $this->course_id = $assignment->course->id;
        $this->course_name = $assignment->course->name;
        $this->course_start_date = $assignment->course->start_date;
        $this->instructor_name = "$extra_info->first_name $extra_info->last_name";
        $this->instructor_email = $extra_info->email;

        $this->save();
    }

}
