<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Score;
use App\Assignment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Traits\JWT;


use App\Exceptions\Handler;
use \Exception;

class SubmissionController extends Controller
{

    use JWT;

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSubmission $request, Submission $submission, Assignment $Assignment, Score $score)
    {

        $response['type'] = 'error';//using an alert instead of a noty because it wasn't working with post message

        $data = $request->validated();
        $data['user_id'] = Auth::user()->id;
        $assignment = $Assignment->find($data['assignment_id']);

        $authorized = Gate::inspect('store', [$submission, $assignment, $assignment->id, $data['question_id']]);

        $assignment_question = DB::table('assignment_question')->where('assignment_id', $assignment->id)
            ->where('question_id', $data['question_id'])
            ->select('points')
            ->first();

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }


        if (env('DB_DATABASE') === 'test_libretext') {
            $data['score'] = $assignment->default_points_per_question;
        } else {
            $submission = json_decode($data['submission']);
            switch ($data['technology']) {
                case('h5p'):
                    $data['score'] = floatval($assignment_question->points) * (floatval($submission->result->score->raw) / floatval($submission->result->score->max));
                    break;
                case('imathas'):
                    $payload = $this->getPayload($submission->jwt);
                 $data['score'] = floatval($assignment_question->points) * floatval($payload->score);
            }

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
