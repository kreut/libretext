<?php

namespace App\Http\Controllers;

use App\Http\Requests\updateLetterGrade;
use App\LetterGrade;
use App\Course;
use Illuminate\Http\Request;
use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\Gate;


class LetterGradeController extends Controller
{
    public function update(updateLetterGrade $request, Course $course, LetterGrade $LetterGrade)
    {
        $response['type'] = 'error';
        /*$authorized = Gate::inspect('updateLetterGrades', [$letterGrade, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }*/
        $data = $request->validated();

        try {
            $LetterGrade->updateOrCreate(
                ['course_id' => $course->id],
                ['letter_grades' => $data['letter_grades']]
            );
            $letter_grades = $this->orderLetterGradesFromHighToLowCutoffs($data);
            foreach ($letter_grades as $cutoff => $letter_grade){
                $response['letter_grades'][] = ['letter_grade' =>  $letter_grade,
                    'min' => $cutoff,
                    'max' => $prev_cutoff ?? '-'];
                $prev_cutoff= $cutoff;
            }
            $response['letter_grades'][count($response['letter_grades'])-1]['min'] = 0;
            $response['type'] = 'success';
            $response['message'] = "Your letter grades have been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the letter grades.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public
    function orderLetterGradesFromHighToLowCutoffs(array $data) {
        $letter_grades_array = explode(',', $data['letter_grades']);
        $letter_grades = [];
        for ($i = 0; $i < count($letter_grades_array) / 2; $i++) {
            $cutoff = $letter_grades_array[2 * $i];
            $letter_grade = $letter_grades_array[2 * $i + 1];
            $letter_grades[$cutoff] = $letter_grade;
        }
        krsort($letter_grades, SORT_NUMERIC);
        return $letter_grades;
    }
}
