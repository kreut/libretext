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
     */
   public function store($submission, $data){
       $this->anon_student_id =  Auth::user()->email ? Auth::user()->email : 'test';
       $this->session_id = session()->get('submission_id');
       $this->time = Carbon::now();
       $this->level = 'some level';
       $this->problem_name = $submission->question_id;
       $this->problem_view = $submission->submission_count;
       $this->outcome = $data['all_correct'] ? 'CORRECT' : 'INCORRECT';
       $this->input = '';
       $this->school = "some school";
       $this->class = "some class";
       $this->save();
   }

}
