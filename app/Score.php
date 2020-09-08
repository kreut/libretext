<?php

namespace App;

use App\Assignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Score extends Model
{
    protected $fillable = ['user_id', 'assignment_id', 'score'];

    public function updateAssignmentScore(int $student_user_id, int $assignment_id, string $submission_files_type)
    {

        //files are for extra credit
        //remediations are for extra credit
        //loop through all of the submitted questions
        //loop through all of the submitted files
        //for each question add the submitted question score + submitted file score and max out at the score for the question

        $assignment_questions = DB::table('assignment_question')->where('assignment_id', $assignment_id)->get();

        foreach ($assignment_questions as $question) {
            $assignment_question_scores_info[$question->question_id]['points'] = $question->points;
        }


        $submissions = DB::table('submissions')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', $student_user_id)->get();
        if ($submissions->isNotEmpty()) {
            foreach ($submissions as $submission) {

                $assignment_question_scores_info[$submission->question_id]['question'] = $submission->score;
            }
        }

        $assignment_score = 0;

        switch ($submission_files_type) {
            case('q'):
                $submission_files = DB::table('submission_files')
                    ->where('assignment_id', $assignment_id)
                    ->where('type', 'q') //'q', 'a', or 0
                    ->where('user_id', $student_user_id)->get();
                if ($submission_files->isNotEmpty()) {
                    foreach ($submission_files as $submission_file) {
                        $assignment_question_scores_info[$submission_file->question_id]['file'] = $submission_file->score;
                    }
                }
                foreach ($assignment_question_scores_info as $score) {
                    $question_points = $score['question'] ?? 0;
                    $file_points = $score['file'] ?? 0;
                    $assignment_score = $assignment_score + min($score['points'], $question_points + $file_points);
                }
                break;
            case('a'):


                $assignment_score_from_questions = $this->getAssignmentScoreFromQuestions($assignment_question_scores_info);

                //get the points from the submission
                $submission_file = DB::table('submission_files')
                    ->where('assignment_id', $assignment_id)
                    ->where('type', 'a') //'q', 'a', or 0
                    ->where('user_id', $student_user_id)->first();

                $points_from_submissions = $assignment_score_from_questions + ($submission_file->score ?? 0);

                //get the total assignment points
                $total_assignment_points = 0;
                foreach ($assignment_questions as $question) {
                    $total_assignment_points = $total_assignment_points + $question->points;

                }

                $assignment_score = min( $total_assignment_points,$points_from_submissions);
                break;

            case('0'):
                $assignment_score = $this->getAssignmentScoreFromQuestions($assignment_question_scores_info);

                break;

        }
        DB::table('scores')
            ->updateOrInsert(
                ['user_id' => $student_user_id, 'assignment_id' => $assignment_id],
                ['score' => $assignment_score]);

    }


    public function getAssignmentScoreFromQuestions(array $assignment_question_scores_info) {
        $assignment_score_from_questions = 0;
        //get the assignment points for the questions
        foreach ($assignment_question_scores_info as $score) {
            $question_points = $score['question'] ?? 0;
            $assignment_score_from_questions = $assignment_score_from_questions + $question_points;
        }

        return $assignment_score_from_questions;
    }
}
