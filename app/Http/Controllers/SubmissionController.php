<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Score;
use App\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

use App\Exceptions\Handler;
use \Exception;

class SubmissionController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Submission $submission, Assignment $Assignment, Score $score)
    {

        $data['user_id'] = $request->user()->id;
        $data['assignment_id'] = $request->input('assignment_id');
        $data['question_id'] = $request->input('question_id');
        $data['submission'] = $request->input('submission');


        $response['type'] = 'danger';//using an alert instead of a noty because it wasn't working with post message

        $assignment = $Assignment->find($data['assignment_id']);

        $authorized = Gate::inspect('store', [$submission, $assignment, $assignment->id, $data['question_id']]);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }


        if (env('DB_DATABASE')) {
            $data['score'] = $assignment->default_points_per_question;
        } else {


        }


        try {

            //do the extension stuff also

            $submission = Submission::where('user_id', '=', $data['user_id'])
                ->where('assignment_id', '=', $data['assignment_id'])
                ->where('question_id', '=', $data['question_id'])
                ->first();

            if ($submission) {

                $submission->submission = $data['submission'];
                $submission->score = $data['score'];
                $submission->save();

            } else {

                Submission::create($data);
            }

            //update the score if it's supposed to be updated
            switch ($assignment->scoring_type) {
                case 'c':
                    $num_submissions_by_assignment = DB::table('submissions')
                        ->where('user_id', $data['user_id'])
                        ->where('assignment_id', $assignment->id)
                        ->count();
                    if ((int)$num_submissions_by_assignment === count($assignment->questions)) {
                        Score::firstOrCreate(['user_id' => $data['user_id'],
                            'assignment_id' => $assignment->id,
                            'score' => 'C']);
                        $response['message'] = "Your assignment has been marked completed.";
                    }
                    $response['type'] = 'success';
                    break;
                case 'p':
                    $score->updateAssignmentScore($data['user_id'], $assignment->id, $assignment->submission_files);
                    $response['type'] = 'success';
                    break;
            }

            $response['message'] = 'Your question submission was saved.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving your response.  Please try again or contact us for assistance.";
        }

        return $response;

    }


}
