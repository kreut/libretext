<?php

namespace App\Console\Commands\OneTimers\Scores;


use App\Assignment;
use App\Course;
use App\Exceptions\Handler;
use App\Submission;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class recomputeUserScoresByCourse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recompute:UserScoresByCourse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'In case there is an issue with score computation course-wise';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function latePenaltyPercent(int $user_id, Assignment $assignment, Carbon $submitted_at)
    {
        $late_deduction_percent = 0;
        if ($assignment->late_policy === 'deduction') {
            $late_deduction_application_period = $assignment->late_deduction_application_period;
            $due = Carbon::parse($assignment->assignToTimingDueDateGivenUserId($user_id));
            if ($late_deduction_application_period !== 'once') {
                $late_deduction_percent = $assignment->late_deduction_percent;
                $max_num_iterations = (int)floor(100 / $late_deduction_percent);
                for ($num_late_periods = 0; $num_late_periods < $max_num_iterations; $num_late_periods++) {
                    if ($due > $submitted_at) {
                        break;
                    }
                    $due->add($late_deduction_application_period);
                }
                $late_deduction_percent = $late_deduction_percent * $num_late_periods;
            }
            if ($late_deduction_application_period === 'once' && $submitted_at > $due) {
                $late_deduction_percent = $assignment->late_deduction_percent;
            }
            return $late_deduction_percent;
        }

        return $late_deduction_percent;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Submission $Submission)
    {
        ///// currently works just for the auto-graded assignments
        try {
            $course_id = 348;
            $late_users = [];
            $course = Course::find($course_id);
            $assignments = $course->assignments;

            DB::beginTransaction();

            foreach ($assignments as $assignment) {
                if (!in_array($assignment->id, [3445, 3443, 3073])) {
                    //ignore the first assignment which looks like an override and the surveys which were created beforehand
                    $questions = DB::table('questions')->whereIn('id', $assignment->questions->pluck('id')->toArray())->get();
                    $question_by_id = [];
                    foreach ($questions as $question) {
                        $question_by_id[$question->id] = $question;
                        echo $question->id . ' ';
                    }
                    $submissions = $Submission->where('assignment_id', $assignment->id)->get();
                    foreach ($submissions as $submission) {
                        $current_score = $submission->score;
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

                        $late_penalty_percent = $this->latePenaltyPercent($submission->user_id, $assignment, Carbon::parse($submission->updated_at));
                        if ($late_penalty_percent) {
                            if (!in_array($submission->user_id, $late_users)) {
                                $late_users[] = $submission->user_id;
                            }

                        }
                        $new_score = Round($new_score * (100 - $late_penalty_percent) / 100, 4);
                        if (abs($current_score - $new_score) > .05) {
                            //only change for rounding issues
                            $new_score = $current_score;
                        }
                        if (in_array($submission->id, [250508, 250703, 251148, 251258, 251625, 252010, 252461, 252496, 252625, 259098, 264714, 264726, 264764])) {
                            //score changes seemed too big
                            $new_score = $current_score;
                        }
                        DB::table('original_submission_scores')->where('submission_id', $submission->id)
                            ->update(['new_score' => $new_score]);
                        echo "$current_score $new_score\r\n";
                    }
                    $new_scores = DB::table('original_submission_scores')
                        ->where('assignment_id', $assignment->id)
                        ->groupBy('user_id')
                        ->select(DB::raw('sum(new_score) as sum, user_id'))
                        ->get();
                    foreach ($new_scores as $new_score) {
                        DB::table('original_assignment_scores')
                            ->where(['assignment_id' => $assignment->id, 'user_id' => $new_score->user_id])
                            ->update(['new_score' => $new_score->sum]);
                    }
                }
            }
            DB::commit();
            dd($late_users);
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            echo "Error: view logs";
            return 1;
        }
        return 0;
    }
}
