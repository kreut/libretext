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

use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\Validator;

class ScoreController extends Controller
{

    public function index(Course $course)
    {

        $authorized = Gate::inspect('viewCourseScores', $course);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }


        //get all user_ids for the user enrolled in the course
        foreach ($course->enrolledUsers as $key => $user) {
            $enrolled_users[$user->id] = "$user->first_name $user->last_name";
            $enrolled_users_last_first[$user->id] = "$user->last_name, $user->first_name ";
        }

        //get all assignments in the course
        $assignments = $course->assignments->sortBy('due');

        if ($assignments->isEmpty()) {
            return ['hasAssignments' => false];
        }

        $scores = $course->scores;
        $extensions = $course->extensions;
        foreach ($extensions as $value) {
            $extension[$value->user_id][$value->assignment_id] = 'Extension';
        }


        //organize the scores by user_id and assignment
        $scores_by_user_and_assignment = [];
        foreach ($scores as $score) {
            $scores_by_user_and_assignment[$score->user_id][$score->assignment_id] = $score->score;
        }

        //now fill in the actual scores
        $rows = [];
        $download_data = [];
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
            $columns['name'] = $name;
            $columns['userId'] = $user_id;
            $download_data[] = $download_row_data;
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

        return ['hasAssignments' => true,
            'table' => compact('rows', 'fields') + ['hasAssignments' => true],
            'download_fields' => $download_fields,
            'download_data' => $download_data];

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Score $score
     * @return \Illuminate\Http\Response
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
                    $scores[$submission_file->user_id]['score'] = $submission_file->score;
                }
            }

            $submissions = $submission->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->get();

            if ($submissions->isNotEmpty()) {
                foreach ($submissions as $key => $submission) {
                    $submission_file_score =  $scores[$submission_file->user_id]['score']  ?? 0;
                    $scores[$submission->user_id]['score'] = $submission_file_score + $submission->score;
                }
            }
            $response['type'] = 'success';
            $response['scores'] = array_values($scores);
            return $response;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the scores' summary.  Please try again or contact us for assistance.";
        }
        return $response;

    }


}
