<?php

namespace App;

use App\Assignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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

        $assignment_score = 0;
        //initialize
        $assignment_question_scores_info = [];
        $question_ids = [];
        foreach ($assignment_questions as $question) {
            $question_ids[] = $question->question_id;
            $assignment_question_scores_info[$question->question_id] = [];
            $assignment_question_scores_info[$question->question_id]['points'] = $question->points;
            $assignment_question_scores_info[$question->question_id]['question'] = 0;
            $assignment_question_scores_info[$question->question_id]['file'] = 0;//need for file uploads
        }

        $submissions = DB::table('submissions')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', $student_user_id)->get();
        if ($submissions->isNotEmpty()) {
            foreach ($submissions as $submission) {
                $assignment_question_scores_info[$submission->question_id]['question'] = $submission->score;
            }
        }
        switch ($submission_files_type) {
            case('q'):

                $submission_files = DB::table('submission_files')
                    ->where('assignment_id', $assignment_id)
                    ->where('type', 'q') //'q', 'a', or 0
                    ->whereIn('question_id', $question_ids)
                    ->where('user_id', $student_user_id)->get();

                if ($submission_files->isNotEmpty()) {
                    foreach ($submission_files as $submission_file) {
                        $assignment_question_scores_info[$submission_file->question_id]['file'] = $submission_file->score
                            ? $submission_file->score
                            : 0;
                    }
                }

                foreach ($assignment_question_scores_info as $score) {
                    $question_points = $score['question'];
                    $file_points = $score['file'];
                    $assignment_score = $assignment_score + $question_points + $file_points;
                }
                break;
            case('a'):
                $assignment_score_from_questions = $assignment_question_scores_info ?
                    $this->getAssignmentScoreFromQuestions($assignment_question_scores_info)
                    : 0;
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

                $assignment_score = min($total_assignment_points, $points_from_submissions);
                break;

            case('0'):
                $assignment_score = $assignment_question_scores_info ?
                    $this->getAssignmentScoreFromQuestions($assignment_question_scores_info)
                    : 0;
                break;

        }
        DB::table('scores')
            ->updateOrInsert(
                ['user_id' => $student_user_id, 'assignment_id' => $assignment_id],
                ['score' => $assignment_score, 'updated_at' => Carbon::now()]);

    }

    public function getUserScoresByCourse(Course $course, User $user)
    {

        $assignments = $course->assignments;
        $assignment_ids = [];
        $scores_released = [];
        $scoring_types = [];
        $scores_by_assignment = [];
        $z_scores_by_assignment = [];


//initialize
        foreach ($assignments as $assignment) {
            $assignment_ids[] = $assignment->id;
            $scores_released[$assignment->id] = $assignment->show_scores;
            $scoring_types[$assignment->id] = $assignment->scoring_type;
            $z_scores_by_assignment[$assignment->id] = 'N/A';
            if ($assignment->scoring_type === 'p') {
                $scores_by_assignment[$assignment->id] = ($assignment->show_scores)
                    ? 0 : 'Not yet released';

            } else {
                $scores_by_assignment[$assignment->id] = 'Incomplete';

            }

        }
        $scores = DB::table('scores')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('user_id', $user->id)
            ->get();
        $statistics = DB::table('scores')
            ->whereIn('assignment_id', $assignment_ids)
            ->select('assignment_id', DB::raw('AVG(score) as average'), DB::raw('STDDEV(score) as std_dev'))
            ->groupBy('assignment_id')
            ->get();
        foreach ($statistics as $key => $value) {
            $statistics_by_assignment[$value->assignment_id] = [
                'average' => $value->average,
                'std_dev' => $value->std_dev];
        }


//show the score for points only if the scores have been released
//otherwise show the score
        foreach ($scores as $key => $value) {
            $assignment_id = $value->assignment_id;
            $score = $value->score;
            if ($scoring_types[$assignment_id] === 'p') {
                if ($scores_released[$assignment_id]) {
                    $scores_by_assignment[$assignment_id] = $score;
                    if ($statistics_by_assignment[$assignment_id]['std_dev'] > 0) {
                        $z_score = ($score - $statistics_by_assignment[$assignment_id]['average']) / $statistics_by_assignment[$assignment_id]['std_dev'];
                        $z_scores_by_assignment[$assignment_id] = round($z_score, 2);
                    } else {
                        $z_scores_by_assignment[$assignment_id] = "Std Dev is 0";
                    }
                }
            } else {
                $scores_by_assignment[$assignment_id] = ($value->score === 'c') ? 'Complete' : 'Incomplete';
            }
        }

        return [$scores_by_assignment, $z_scores_by_assignment];

    }


    public function getAssignmentScoreFromQuestions(array $assignment_question_scores_info)
    {

        $assignment_score_from_questions = 0;
        //get the assignment points for the questions
        foreach ($assignment_question_scores_info as $score) {
            $question_points = $score['question'] ?? 0;
            $assignment_score_from_questions = $assignment_score_from_questions + $question_points;
        }

        return $assignment_score_from_questions;
    }
}
