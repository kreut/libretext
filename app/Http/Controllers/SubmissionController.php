<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Score;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $data['user_id'] = $request->user()->id;
        $data['assignment_id'] = $request->input('assignment_id');
        $data['question_id'] = $request->input('question_id');
        $data['submission'] = $request->input('submission');

        //check if assignment is in question and assignment is one of theirs and due data

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
        if ($num_submissions_by_assignment >= 2) {
            Score::firstOrCreate(['user_id' => $data['user_id'],
                'assignment_id' => $data['assignment_id'],
                'score' => 'C']);
        }
    }


}
