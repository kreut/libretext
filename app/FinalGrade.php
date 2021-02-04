<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FinalGrade extends Model
{
    protected $guarded = [];

    public function defaultLetterGrades(){
        return '90,A,80,B,70,C,60,D,0,F';
    }
    public function setDefaultLetterGrades($course_id)
    {
        $this->create(['course_id' => $course_id,
            'letter_grades' => $this->defaultLetterGrades()]);
    }

    public function getLetterGradesAsArray($letter_grades)
    {
        $letter_grade_array = explode(',', $letter_grades);
        $response = [];
        for ($i = 0; $i < count($letter_grade_array) / 2; $i++) {
            $response [] = [
                'letter_grade' => $letter_grade_array[2 * $i + 1],
                'min' => "{$letter_grade_array[2 * $i]}%",
                'max' => ($i >= 1) ? "<{$letter_grade_array[2 * $i - 2]}%" : '-'
            ];
        }
        return $response;
    }
}
