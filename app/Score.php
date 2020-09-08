<?php

namespace App;

use App\Assignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Score extends Model
{
    protected $fillable = ['user_id', 'assignment_id', 'score'];

    public function updateAssignmentScore(int $student_user_id, int $assignment_id)
    {

        //files are for extra credit
        //remediations are for extra credit
        //loop through all of the submitted questions
        //loop through all of the submitted files
        //for each question add the submitted question score + submitted file score and max out at the score for the question

        $assignment_questions = DB::table('assignment_question')->where('assignment_id', $assignment_id)->get();

        foreach ($assignment_questions as $question) {
            $scores[$question->question_id]['points'] = $question->points;
        }


        $submissions = DB::table('submissions')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', $student_user_id)->get();
        if ($submissions->isNotEmpty()) {
            foreach ($submissions as $submission) {

                $scores[$submission->question_id]['question'] = $submission->score;
            }
        }
        $submission_files = DB::table('submission_files')
            ->where('assignment_id', $assignment_id)
            ->where('type', 'q')
            ->where('user_id', $student_user_id)->get();


        if ($submission_files->isNotEmpty()) {
            foreach ($submission_files as $submission_file) {
                $scores[$submission_file->question_id]['file'] = $submission_file->score;
            }
        }
        $assignment_score = 0;

        foreach ($scores as $score) {

            $assignment_score = $assignment_score + min($score['points'], $score['question'] + $score['file']);
        }

        DB::table('scores')
            ->updateOrInsert(
            ['user_id' => $student_user_id, 'assignment_id'=> $assignment_id],
            ['score' => $assignment_score]);

    }
}
