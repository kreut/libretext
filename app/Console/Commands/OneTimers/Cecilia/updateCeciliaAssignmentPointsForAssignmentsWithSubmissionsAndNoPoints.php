<?php

namespace App\Console\Commands\OneTimers\Cecilia;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Course;
use App\Submission;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updateCeciliaAssignmentPointsForAssignmentsWithSubmissionsAndNoPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ceciliaAssignmentPointsForAssignmentsWithSubmissionsAndNoPoints';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Submission $Submission)
    {
/*49970,
 * +"CONCAT(first_name, " ", last_name)": "Maria Cecilia Rosales"
  +"email": "maria.rosales@estrellamountain.edu"
  +"course": "SPA101- Spanish 1A (Libro Libre ReMix)_ Hybrid Spring 24 Rosales"
  +"assignment": "2.8 Adjetivos descriptivos: La personalidad"

+"52476,
  CONCAT(first_name, " ", last_name)": "Adriana Aguirre"
  +"email": "adriana.aguirre@estrellamountain.edu"
  +"course": "SPA101- online Spanish 1A (Libro Libre ReMix)_ Aguirre_Spring24"
  +"assignment": "2.8 Adjetivos descriptivos: La personalidad"

 */
        try {
            $assignment_ids = [49970, 52476];
            $late_users = [];
            DB::beginTransaction();
            $assignments = Assignment::whereIn('id', $assignment_ids)->get();
            foreach ($assignments as $assignment) {
                AssignmentSyncQuestion::where('assignment_id', $assignment->id)->update(['points' => '5.000', 'weight' => null]);
                $assignment->points_per_question = 'number of points';
                $assignment->default_points_per_question = 5;
                $assignment->save();
                $questions = DB::table('questions')->whereIn('id', $assignment->questions->pluck('id')->toArray())->get();
                $question_by_id = [];
                foreach ($questions as $question) {
                    $question_by_id[$question->id] = $question;
                    //echo $question->id . ' ';
                }
                $submissions = $Submission->where('assignment_id', $assignment->id)->get();
                foreach ($submissions as $submission) {
                    $assignment_question = DB::table('assignment_question')
                        ->where('assignment_id', $assignment->id)
                        ->where('question_id', $submission->question_id)
                        ->select('id',
                            'points',
                            'question_id',
                            'assignment_id',
                            'completion_scoring_mode',
                            'open_ended_submission_type')
                        ->first();

                    $current_submission = json_decode($submission->submission);
                    $score = 0;

                    switch ($question_by_id[$submission->question_id]->technology) {
                        case('h5p'):
                            $proportion_correct = $Submission->getProportionCorrect('h5p', $current_submission);
                            $score = $assignment->scoring_type === 'p'
                                ? floatval($assignment_question->points) * $proportion_correct
                                : $Submission->computeScoreForCompletion($assignment_question);
                            break;
                        case('imathas'):
                            $proportion_correct = $Submission->getProportionCorrect('imathas', $current_submission);
                            $score = $assignment->scoring_type === 'p'
                                ? floatval($assignment_question->points) * $proportion_correct
                                : $Submission->computeScoreForCompletion($assignment_question);
                            break;
                        case('webwork'):

                            $proportion_correct = $Submission->getProportionCorrect('webwork', (object)$current_submission);//
                            //Log::info( $submission_score->result);
                            $score = $assignment->scoring_type === 'p'
                                ? floatval($assignment_question->points) * $proportion_correct
                                : $Submission->computeScoreForCompletion($assignment_question);
                            break;
                    }
                    $hint_penalty = $Submission->getHintPenalty($submission->user_id, $assignment, $submission->question_id);
                    $num_deductions_to_apply = $submission->submission_count - 1;
                    $proportion_of_score_received = 1 - (($num_deductions_to_apply * $assignment->number_of_allowed_attempts_penalty + $hint_penalty) / 100);
                    $new_score = max($score * $proportion_of_score_received, 0);

                    $late_penalty_percent = $submission->latePenaltyPercentGivenUserId($submission->user_id, $assignment, Carbon::parse($submission->updated_at));
                    if ($late_penalty_percent) {
                        if (!in_array($submission->user_id, $late_users)) {
                            $late_users[] = $submission->user_id;
                        }

                    }
                    $new_score = Round($new_score * (100 - $late_penalty_percent) / 100, 4);
                    $submission->score = $new_score;
                    $submission->save();
                    // echo "$current_score $new_score\r\n";
                }
            }
            DB::commit();
            $assignments = Assignment::whereIn('id', $assignment_ids)->get();
            foreach ($assignments as $assignment) {
                $results = DB::table('submissions')
                    ->select('user_id', DB::raw('SUM(score) as total_score'))
                    ->where('assignment_id', $assignment->id)
                    ->groupBy('user_id')
                    ->get();
                foreach ($results as $result) {
                    DB::table('scores')->where('assignment_id', $assignment->id)
                        ->where('user_id', $result->user_id)
                        ->update(['score' => $result->total_score, 'updated_at' => now()]);
                }
            }
            echo "done";
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
