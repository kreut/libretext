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
    public function store(Request $request, Submission $submission)
    {

        $data['user_id'] = $request->user()->id;
        $data['assignment_id'] = $request->input('assignment_id');
        $data['question_id'] = $request->input('question_id');
        $data['submission'] = $request->input('submission');


        $response['type'] = 'error';

        $authorized = Gate::inspect('store', [$submission, Assignment::find($data['assignment_id']), $data['assignment_id'], $data['question_id']]);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            //do the extension stuff also


            $submission = Submission::where('user_id', '=', $data['user_id'])
                ->where('assignment_id', '=', $data['assignment_id'])
                ->where('question_id', '=', $data['question_id'])
                ->first();

            $submission ? $submission->update(['submission' => $data['submission']])
                : Submission::Create($data);

            //update the score if it's supposed to be updated
            $num_submissions_by_assignment = DB::table('submissions')
                ->where('user_id', '=', $data['user_id'])
                ->where('assignment_id', '=', $data['assignment_id'])
                ->count();
            $response['message'] = 'Your response has been recorded.';
            if ($num_submissions_by_assignment >= 2) {
                Score::firstOrCreate(['user_id' => $data['user_id'],
                    'assignment_id' => $data['assignment_id'],
                    'score' => 'C']);
                $response['message'] = "Your assignment has been marked completed.";
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving your response.  Please try again or contact us for assistance.";
        }
        return $response;
    }


}
