<?php

namespace App\Http\Controllers;

use App\AssignmentFile;
use App\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AssignmentFileController extends Controller
{

    public function getAssignmentFilesByAssignment(Request $request, Assignment $assignment)
    {
        $assignmentFilesByUser = [];
        foreach ($assignment->assignmentFiles as $key => $assignment_file) {
            $assignment_file->needs_grading = $assignment_file->date_graded ?
                Carbon::parse($assignment_file->date_submitted) > Carbon::parse($assignment_file->date_graded)
                : true;
            $assignmentFilesByUser[$assignment_file->user_id] = $assignment_file;
        }
        $user_and_assignment_file_info = [];
        foreach ($assignment->course->enrolledUsers as $key => $user) {
            //get the assignment info, getting the temporary url of the first submission for viewing
            $submission = $assignmentFilesByUser[$user->id]->submission ?? null;
            $all_info = ['id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'submission' => $submission,
                'url' => ($submission && $key === 0) ? $this->getAssignmentSubmissionTemporaryUrl($assignment->id, $submission)
                    : null];

            $user_and_assignment_file_info[] = $all_info;
        }

        return $user_and_assignment_file_info;
    }

    public function getTemporaryUrl(Request $request)
    {
        return $this->getAssignmentSubmissionTemporaryUrl($request->assignment_id, $request->submission);

    }

    public function getAssignmentSubmissionTemporaryUrl($assignment_id, $submission, $time = 5)
    {
        return Storage::disk('s3')->temporaryUrl("assignments/$assignment_id/$submission", now()->addMinutes($time));
    }

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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param \App\AssignmentFile $assignmentFile
     * @return \Illuminate\Http\Response
     */
    public function show(AssignmentFile $assignmentFile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\AssignmentFile $assignmentFile
     * @return \Illuminate\Http\Response
     */
    public function edit(AssignmentFile $assignmentFile)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\AssignmentFile $assignmentFile
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AssignmentFile $assignmentFile)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\AssignmentFile $assignmentFile
     * @return \Illuminate\Http\Response
     */
    public function destroy(AssignmentFile $assignmentFile)
    {
        //
    }
}
