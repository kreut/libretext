<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Score;
use App\Course;
use App\User;
use App\Assignment;
use App\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
                $score = $scores_by_user_and_assignment[$user_id][$assignment->id] ?? '-';
                if (isset($extension[$user_id][$assignment->id])) {
                    $score = 'Extension';
                }

                $columns[$assignment->id] = $score;
                $download_row_data["{$assignment->id}"] = $score;
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

        try {


            //todo: validate the data as a possible score (completed or not
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


}
