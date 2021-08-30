<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DataShop extends Model
{

    public $timestamps = false;

    /**
     * @param $submission
     * @param $data
     * @param $assignment
     */
   public function store($submission, $data, Assignment $assignment){
       $this->anon_student_id =  Auth::user()->email ? Auth::user()->email : 'test';
       $this->session_id = session()->get('submission_id');
       $this->time = Carbon::now();
       $this->level = $assignment->id;
       $this->problem_name = $submission->question_id;
       $this->problem_view = $submission->submission_count;
       $this->outcome = $data['all_correct'] ? 'CORRECT' : 'INCORRECT';
       $this->school = $assignment->course->school_id;
       $this->class = $assignment->course->id;
       $this->save();
   }

}
