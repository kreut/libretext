<?php

namespace App\Console\Commands;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Exceptions\Handler;
use App\Score;
use App\Submission;
use App\SubmissionsRecomputedScore;
use App\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecomputeQuestionScoresByAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recompute:QuestionScoresByAssignment {assignment_id}';
////art recompute:QuestionScoresByAssignment 161387//
    /**
     * The console command description.
     *recompute:QuestionScoresByAssignment 161387
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
    public function handle(Submission             $submission,
                           AssignmentSyncQuestion $assignmentSyncQuestion,
                           Score                  $Score)
    {
        $assignment_id = $this->argument('assignment_id');
        $assignment = Assignment::find($assignment_id);
        try {
            $user_ids = [];
            DB::beginTransaction();
            $submissions = $submission->where('assignment_id', $assignment->id)->get();
            $questions = $assignment->questions;
            foreach ($submissions as $submission) {
                $question = $questions->where('id', $submission->question_id)->first();
                $data['user_id'] = $submission->user_id;
                $data['technology'] = $question['technology'];
                $data['submission'] = $submission->submission;
                $assignment_question = $assignmentSyncQuestion->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->first();

                $score = $submission->computeScore($assignment,
                    $question,
                    $Score,
                    $assignmentSyncQuestion,
                    $assignment_question,
                    $data);
                $hint_penalty = $submission->getHintPenalty($data['user_id'], $assignment, $question['id']);
                $num_deductions_to_apply = 0;
                $number_of_allowed_attempts_penalty = 0;
                if (in_array($assignment->assessment_type, ['learning tree', 'real time'])) {
                    $num_deductions_to_apply = $submission->submission_count - 1;
                    $number_of_allowed_attempts_penalty = $assignment->number_of_allowed_attempts_penalty;
                }
                $proportion_of_score_received = 1 - (($num_deductions_to_apply * $number_of_allowed_attempts_penalty + $hint_penalty) / 100);
                $score = max($score * $proportion_of_score_received, 0);
                $updated_at = Carbon::parse($submission->created_at, 'UTC');
                $score = $submission->applyLatePenalyToScore($assignment, $score, $data['user_id'], $updated_at);

                if ((float)$score !== (float)$submission->score) {
                    $submissionsRecomputedScore = SubmissionsRecomputedScore::firstOrNew(
                        ['submission_id' => $submission->id]
                    );

                    $submissionsRecomputedScore->score = $score;
                    $user_ids[] = $submission->user_id;
                    $submissionsRecomputedScore->original_score = $submission->score;
                    $submissionsRecomputedScore->save();
                    $submission->score = $score;
                    $submission->save();
                }
            }
            $user_ids = array_unique($user_ids);
            echo "Number of students: " . count($user_ids) . "\r\n";
            foreach ($user_ids as $user_id) {
                $old_score = $Score->where('user_id', $user_id)->where('assignment_id', $assignment->id)->first()->score;
                $Score->updateAssignmentScore($user_id, $assignment->id, $assignment->lms_grade_passback === 'automatic');
                $new_score = $Score->where('user_id', $user_id)->where('assignment_id', $assignment->id)->first()->score;
                DB::table('assignments_recomputed_scores')->insert(['user_id' => $user_id,
                    'assignment_id' => $assignment->id,
                    'old_score' => $old_score,
                    'new_score' => $new_score,
                    'created_at' => now(),
                    'updated_at' => now()]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
