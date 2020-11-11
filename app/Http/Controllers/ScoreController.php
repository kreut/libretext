<?php

namespace App\Http\Controllers;

use App\Score;
use App\Course;
use App\SubmissionFile;
use App\User;
use App\Assignment;
use App\Submission;
use App\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\Validator;

class ScoreController extends Controller
{

    public function getTotalPointsByAssignmentId(array $assignment_ids)
    {
        $total_points_by_assignment_id = [];
        $adapt_total_points = DB::table('assignment_question')
            ->selectRaw('assignment_id, sum(points) as sum')
            ->whereIn('assignment_id', $assignment_ids)
            ->groupBy('assignment_id')
            ->get();
        $external_total_points = DB::table('assignments')
                    ->whereIn('id', $assignment_ids)
                    ->where('source','x')
                    ->get();

        foreach ($adapt_total_points as $key => $value) {
            $total_points_by_assignment_id[$value->assignment_id] = $value->sum;
        }
        foreach ($external_total_points as $key => $value){
            $total_points_by_assignment_id[$value->id] = $value->external_source_points;
        }

        return $total_points_by_assignment_id;
    }

    public function getAssignmentGroupWeights(int $course_id)
    {
        $assignment_groups_by_assignment_id = [];
        $assignment_group_weights_info = [];

        $assignment_group_weights = DB::table('assignments')
            ->join('assignment_group_weights', 'assignments.assignment_group_id', '=', 'assignment_group_weights.assignment_group_id')
            ->where('assignments.course_id', $course_id)
            ->select('assignments.id', 'assignments.assignment_group_id', 'assignment_group_weights.assignment_group_weight')
            ->get();


//create arrays for assignment_group_ids, weights, and counts
        foreach ($assignment_group_weights as $key => $value) {
            $assignment_groups_by_assignment_id[$value->id] = $value->assignment_group_id;
            if (isset($assignment_group_weights_info[$value->assignment_group_id])) {
                $assignment_group_weights_info[$value->assignment_group_id]['count']++;
            } else {
                $assignment_group_weights_info[$value->assignment_group_id]['weight'] = $value->assignment_group_weight;
                $assignment_group_weights_info[$value->assignment_group_id]['count'] = 1;
            }
        }
        return [$assignment_group_weights_info, $assignment_groups_by_assignment_id];
    }

    public function getScoresByUserIdAndAssignment($scores, array $assignment_groups_by_assignment_id, array $total_points_by_assignment_id)
    {
        //organize the scores by user_id and assignment
        $scores_by_user_and_assignment = [];
        $proportion_scores_by_user_and_assignment_group = [];
        foreach ($scores as $score) {
            $scores_by_user_and_assignment[$score->user_id][$score->assignment_id] = $score->score;


            $group_id = $assignment_groups_by_assignment_id[$score->assignment_id];
            //init if needed
            $proportion_scores_by_user_and_assignment_group[$score->user_id][$group_id] = $proportion_scores_by_user_and_assignment_group[$score->user_id][$group_id] ?? 0;

            $score_as_proportion = $score->score / $total_points_by_assignment_id[$score->assignment_id];
            $proportion_scores_by_user_and_assignment_group[$score->user_id][$group_id] += $score_as_proportion;

        }
        return [$scores_by_user_and_assignment, $proportion_scores_by_user_and_assignment_group];
    }

    public function getFinalWeightedScores(Course $course, array $proportion_scores_by_user_and_assignment_group, array $assignment_group_weights_info)
    {
        $final_weighted_scores = [];
        foreach ($course->enrolledUsers as $key => $user) {
            $final_weighted_scores[$user->id] = 0;
            if (isset($proportion_scores_by_user_and_assignment_group[$user->id])) {
                foreach ($proportion_scores_by_user_and_assignment_group[$user->id] as $group_id => $group_score) {
                    $final_weighted_scores[$user->id] += $assignment_group_weights_info[$group_id]['weight'] * $group_score / $assignment_group_weights_info[$group_id]['count'];
                }
            }
        }
        foreach ($course->enrolledUsers as $key => $user) {
            $final_weighted_scores[$user->id] = Round($final_weighted_scores[$user->id], 2) . '%';
        }
        return $final_weighted_scores;
    }

    public function getAssignmentIds($assignments)
    {
        return $assignments->map(function ($assignment) {
            return collect($assignment->toArray())
                ->all()['id'];
        })->toArray();
    }


    public function getFinalTableInfo(array $assignment_ids,
                                      array $enrolled_users,
                                      array $enrolled_users_last_first,
                                      $assignments,
                                      array $final_weighted_scores,
                                    array $scores_by_user_and_assignment)
    {
        {
            $final_assignment_id = max($assignment_ids) + 1;
            //now fill in the actual scores
            $rows = [];
            $download_rows = [];
            foreach ($enrolled_users as $user_id => $name) {
                $columns = [];
                $download_row_data = ['name' => $enrolled_users_last_first[$user_id]];
                foreach ($assignments as $assignment) {
                    $default_score = ($assignment->scoring_type === 'p') ? 0 : 'Incomplete';
                    $score = $scores_by_user_and_assignment[$user_id][$assignment->id] ?? $default_score;
                    if (isset($extension[$user_id][$assignment->id])) {
                        $score .= ' (E)';
                    }
                    if ($assignment->scoring_type === 'c') {
                        $score = ($score === 'c') ? 'Complete' : 'Incomplete';//easier to read
                    }
                    $columns[$assignment->id] = $score;
                    $download_row_data["{$assignment->id}"] = str_replace(' (E)', '', $score);//get rid of the extension info
                }
                $columns[$final_assignment_id] = $final_weighted_scores[$user_id];
                $download_row_data[$final_assignment_id] = $final_weighted_scores[$user_id];
                $columns['name'] = $name;
                $columns['userId'] = $user_id;
                $download_rows[] = $download_row_data;
                $rows[] = $columns;
            }

            $fields = [['key' => 'name',
                'label' => 'Name',
                'sortable' => true,
                'stickyColumn' => true]];
            $download_fields = new \stdClass();
            $download_fields->LastFirst = 'name';
            foreach ($assignments as $assignment) {
                $field = ['key' => "$assignment->id", 'label' => $assignment->name];
                $download_fields->{$assignment->name} = $assignment->id;
                array_push($fields, $field);
            }
            array_push($fields, ['key' => "$final_assignment_id", 'label' => 'Weighted Score']);
            $download_fields->{"Weighted Score"} = $final_assignment_id;
            return [$rows, $fields, $download_rows, $download_fields];

        }
    }

    public function index(Course $course)
    {

        $authorized = Gate::inspect('viewCourseScores', $course);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }


        //get all user_ids for the user enrolled in the course
        $enrolled_users = [];
        foreach ($course->enrolledUsers as $key => $user) {
            $enrolled_users[$user->id] = "$user->first_name $user->last_name";
            $enrolled_users_last_first[$user->id] = "$user->last_name, $user->first_name ";
        }

        //get all assignments in the course
        $assignments = $course->assignments->sortBy('due');


        if ($assignments->isEmpty()) {
            return ['hasAssignments' => false];
        }

        $assignment_ids = $this->getAssignmentIds($assignments);
        $total_points_by_assignment_id = $this->getTotalPointsByAssignmentId($assignment_ids);
        $scores = $course->scores;
        $extensions = $course->extensions;
        foreach ($extensions as $value) {
            $extension[$value->user_id][$value->assignment_id] = 'Extension';
        }
        [$assignment_group_weights_info, $assignment_groups_by_assignment_id] = $this->getAssignmentGroupWeights($course->id);
        [$scores_by_user_and_assignment, $proportion_scores_by_user_and_assignment_group] = $this->getScoresByUserIdAndAssignment($scores, $assignment_groups_by_assignment_id, $total_points_by_assignment_id);
        $final_weighted_scores = $this->getFinalWeightedScores($course, $proportion_scores_by_user_and_assignment_group, $assignment_group_weights_info);
        [$rows, $fields, $download_rows, $download_fields] = $this->getFinalTableInfo($assignment_ids, $enrolled_users, $enrolled_users, $assignments, $final_weighted_scores, $scores_by_user_and_assignment);

        return ['hasAssignments' => true,
            'table' => compact('rows', 'fields') + ['hasAssignments' => true],
            'download_fields' => $download_fields,
            'download_rows' => $download_rows];
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param User $user
     * @param Score $score
     * @return mixed
     * @throws Exception
     */
    public
    function update(Request $request, Assignment $assignment, User $user, Score $score)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$score, $assignment->id, $user->id]);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }

        switch (Assignment::find($assignment->id)->scoring_type) {
            case('p'):
                $validator = Validator::make($request->all(), [
                    'score' => 'required|numeric|min:0|not_in:0'
                ]);

                if ($validator->fails()) {
                    $response['message'] = $validator->errors()->first('score');
                    return $response;
                }
                break;

            case('c'):
                //nothing to validate


                break;


        }

        try {

            Score::updateOrCreate(
                ['user_id' => $user->id, 'assignment_id' => $assignment->id],
                ['score' => $request->score]
            );

            $response['type'] = 'success';
            $response['message'] = 'The score has been updated.';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the score.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getScoreByAssignmentAndStudent(Request $request, Assignment $assignment, User $user, Score $Score)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getScoreByAssignmentAndStudent', [$Score, $assignment->id, $user->id]);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            $score = $Score->where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->first();
            $response['score'] = $score ? $score->score : 0;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the score.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function getScoresByAssignmentAndQuestion(Request $request, Assignment $assignment, Question $question, SubmissionFile $submissionFile, Submission $submission, Score $Score)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getScoreByAssignmentAndQuestion', [$Score, $assignment]);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $scores = [];

            $submissionFiles = $submissionFile->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->get();
            if ($submissionFiles->isNotEmpty()) {
                foreach ($submissionFiles as $key => $submission_file) {
                    $scores[$submission_file->user_id] = $submission_file->score;
                }
            }

            $submissions = $submission->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->get();

            if ($submissions->isNotEmpty()) {
                foreach ($submissions as $key => $submission) {
                    $submission_file_score = $scores[$submission->user_id] ?? 0;
                    $scores[$submission->user_id] = $submission_file_score + $submission->score;
                }
            }

            $response['type'] = 'success';
            $response['scores'] = array_values($scores);
            return $response;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the scores summary.  Please try again or contact us for assistance.";
        }
        return $response;

    }


}
