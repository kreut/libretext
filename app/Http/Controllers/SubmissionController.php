<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

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


        $submission = Submission::where('user_id', '=', $data['user_id'])
            ->where('assignment_id', '=', $data['assignment_id'])
            ->where('question_id', '=', $data['question_id'])
            ->first();

        $submission ? $submission->update(['submission' => $data['submission']])
                     : Submission::Create($data);

        //update the grade if it's supposed to be updated
        $num_submissions_by_assignment = DB::table('submissions')
            ->where('user_id', '=', $data['user_id'])
            ->where('assignment_id', '=', $data['assignment_id'])
            ->count();
        if ($num_submissions_by_assignment >= 2) {
            Grade::firstOrCreate(['user_id' => $data['user_id'],
                'assignment_id' => $data['assignment_id'],
                'grade' => 'C']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Submission $submission
     * @return \Illuminate\Http\Response
     */
    public function show(Submission $submission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Submission $submission
     * @return \Illuminate\Http\Response
     */
    public function edit(Submission $submission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Submission $submission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Submission $submission)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Submission $submission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Submission $submission)
    {
        //
    }
}
