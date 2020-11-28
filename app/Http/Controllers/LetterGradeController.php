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
    private $default_letter_grades;

    public function __construct()
    {
        $this->default_letter_grades = '90,A,80,B,70,C,60,D,0,F';

    }

    public function roundScores(Request $request, Course $course, int $roundScores, LetterGrade $LetterGrade)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('roundScores', [$LetterGrade, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $LetterGrade->updateOrCreate(
                ['course_id' => $course->id],
                ['round_scores' => !$roundScores]
            );

            $response['type'] = 'success';
            $round_scores_message = ((int)$roundScores === 0) ? "will" : "will not";
            $response['message'] = "Scores <strong>$round_scores_message</strong> be rounded up to the nearest integer.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the round scores option.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function releaseLetterGrades(Request $request, Course $course, int $releaseLetterGrades, LetterGrade $LetterGrade)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('releaseLetterGrades', [$LetterGrade, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $LetterGrade->updateOrCreate(
                ['course_id' => $course->id],
                ['letter_grades_released' => !$releaseLetterGrades]
            );

            $response['type'] = 'success';
            $release_grades_message = ((int)$releaseLetterGrades === 0) ? "are" : "are not";
            $response['message'] = "The letter grades <strong>$release_grades_message</strong> released.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating whether the letter grades are released.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getDefaultLetterGrades()
    {
        $response['default_letter_grades'] = $this->getLetterGradesAsArray($this->default_letter_grades);
        return $response;
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

    public function getCourseLetterGrades(Request $request, Course $course, LetterGrade $LetterGrade)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getCourseLetterGrades', [$LetterGrade, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['letter_grades'] = $course->letterGrades && $course->letterGrades->letter_grades
                ? $this->getLetterGradesAsArray($course->letterGrades->letter_grades)
                : $this->getDefaultLetterGrades()['default_letter_grades'];
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the course letter grades.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function update(updateLetterGrade $request, Course $course, LetterGrade $LetterGrade)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateLetterGrades', [$LetterGrade, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();
        $letter_grades = $this->orderLetterGradesFromHighToLowCutoffs($data);
        $formatted_letter_grades = '';
        foreach ($letter_grades as $key => $value) {
            $formatted_letter_grades .= "$key,$value,";
        }
        $formatted_letter_grades = rtrim($formatted_letter_grades, ',');
        try {
            $LetterGrade->updateOrCreate(
                ['course_id' => $course->id],
                ['letter_grades' => $formatted_letter_grades]
            );
            $response['letter_grades'] = $this->getLetterGradesAsArray($formatted_letter_grades);
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
    function orderLetterGradesFromHighToLowCutoffs(array $data)
    {
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
