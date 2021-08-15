<?php

namespace App;


use App\Http\Requests\UpdateScoresRequest;
use App\Jobs\ProcessPassBackByUserIdAndAssignment;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use App\Traits\Statistics;

use Carbon\Carbon;

class Score extends Model
{
    use Statistics;

    protected $fillable = ['user_id', 'assignment_id', 'score'];

    /**
     * @param UpdateScoresRequest $request
     * @param Assignment $assignment
     * @param Question $question
     * @param $model
     * @return array
     * @throws Exception
     */
    public function handleUpdateScores(UpdateScoresRequest $request,
                                       Assignment          $assignment,
                                       Question            $question,
                                                           $model)
    {

        $response['type'] = 'error';
        $submission = new Submission();
        $authorized = Gate::inspect('updateScores', [$submission, $assignment, $question, $request->user_ids]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();
        try {
            $apply_to = $request->apply_to;
            $new_score = $data['new_score'];
            $submissions = $apply_to
                ? $model->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->whereIn('user_id', $request->user_ids)
                    ->get()
                : $model->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->whereNotIn('user_id', $request->user_ids)
                    ->get();
            DB::beginTransaction();
            $lti_launches_by_user_id = $assignment->ltiLaunchesByUserId();
            $ltiGradePassBack = new LtiGradePassback();
            foreach ($submissions as $submission) {
                $adjustment = $new_score - $submission->score;
                $submission->score = $new_score;
                $submission->save();
                $score = new Score();
                $assignment_score = $score->where('assignment_id', $assignment->id)
                    ->where('user_id', $submission->user_id)
                    ->first();
                if (!$assignment_score) {
                    $assignment_score = new Score();
                    $assignment_score->user_id = $submission->user_id;
                    $assignment_score->assignment_id = $assignment->id;
                }
                $assignment_score->score += $adjustment;
                $assignment_score->save();
                if (isset($lti_launches_by_user_id[$submission->user_id])) {
                    $ltiGradePassBack->initPassBackByUserIdAndAssignmentId($assignment_score->score, $lti_launches_by_user_id[$submission->user_id]);
                }
            }
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The scores have been updated.';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the scores.  Please refresh the page and try again or contact us for assistance.";
        }
        return $response;
    }


    public function updateAssignmentScore(int    $student_user_id,
                                          int    $assignment_id,
                                          string $assessment_type)
    {

        //files are for extra credit
        //remediations are for extra credit
        //loop through all of the submitted questions
        //loop through all of the submitted files
        //for each question add the submitted question score + submitted file score and max out at the score for the question
        $assignment = Assignment::find($assignment_id);
        $assignment_questions = DB::table('assignment_question')
            ->where('assignment_id', $assignment_id)
            ->get();

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
        if ($assessment_type === 'delayed') {
            $submission_files = DB::table('submission_files')
                ->where('assignment_id', $assignment_id)
                ->whereIn('type', ['q', 'text', 'audio']) //'q', 'a', or 0
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
        } else {
            $assignment_score = $assignment_question_scores_info ?
                $this->getAssignmentScoreFromQuestions($assignment_question_scores_info)
                : 0;
        }
        DB::table('scores')
            ->updateOrInsert(
                ['user_id' => $student_user_id, 'assignment_id' => $assignment_id],
                ['score' => $assignment_score, 'updated_at' => Carbon::now()]);

        $lti_launch = DB::table('lti_launches')
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $student_user_id)
            ->first();
        if ($lti_launch) {
            $ltiGradePassBack = new LtiGradePassback();
            $ltiGradePassBack->initPassBackByUserIdAndAssignmentId($assignment_score, $lti_launch);
        }
    }

    public function getUserScoresByAssignment(Course $course, User $user)
    {

        $assignments = $course->assignments;
        $assignment_ids = [];
        $scores_released = [];
        $scores_by_assignment = [];
        $z_scores_by_assignment = [];

//initialize
        foreach ($assignments as $assignment) {
            $assignment_ids[] = $assignment->id;
            $scores_released[$assignment->id] = $assignment->show_scores;
            $z_scores_by_assignment[$assignment->id] = 'N/A';
            $scores_by_assignment[$assignment->id] = ($assignment->show_scores) ? 0 : 'Not yet released';
        }
        $scores = DB::table('scores')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('user_id', $user->id)
            ->get();


        $mean_and_std_dev_by_assignment = $this->getMeanAndStdDevByColumn('scores', 'assignment_id', $assignment_ids, 'assignment_id');


//show the score for points only if the scores have been released
//otherwise show the score

        foreach ($scores as $key => $value) {
            $assignment_id = $value->assignment_id;
            $score = $value->score;
            if ($scores_released[$assignment_id]) {
                $scores_by_assignment[$assignment_id] = $score;
                $z_scores_by_assignment[$assignment_id] = $this->computeZScore($score, $mean_and_std_dev_by_assignment[$assignment_id]);
            }
        }


        return [$scores_by_assignment, $z_scores_by_assignment];

    }

    /**
     * @param array $assignment_question_scores_info
     * @return int|mixed
     */
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
