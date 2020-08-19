<?php

namespace App\Http\Controllers;

use App\Upload;
use App\Assignment;
use App\AssignmentFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UploadController extends Controller
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
    public function storeAssignmentFile(Request $request, AssignmentFile $assignmentFile, Assignment $assignment)
    {


        $response['type'] = 'error';
        $assignment_id = $request->assignmentId;
        $authorized = Gate::inspect('uploadAssignmentFile', [$assignmentFile, $assignment->find($assignment_id)]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            //validator put here because I wasn't using vform so had to manually handle errors

            //wait 30 seconds between uploads
            //no more than 10 uploads per assignment
            //delete the file if there was an exception???

            $validator = Validator::make($request->all(), [
                'assignmentFile' => ['required', 'mimes:pdf', 'max:500000']
            ]);

            if ($validator->fails()) {
                $response['message'] = $validator->errors()->first('assignmentFile');
               return $response;
            }

            //save locally and to S3
            $submission = $request->file('assignmentFile')->store("assignments/$assignment_id", 'local');
           $submissionContents = Storage::disk('local')->get($submission);
            Storage::disk('s3')->put("assignments/$assignment_id/$submission",  $submissionContents);

            $assignmentFile->updateOrCreate(
                ['user_id' => Auth::user()->id, 'assignment_id' => $assignment_id],
                ['submission' => basename($submission),
                    'original_filename' => $request->file('assignmentFile')->getClientOriginalName(),
                    'date_submitted' => Carbon::now()]
            );
            $response['type'] = 'success';
            $response['message'] = 'Your assignment submission has been saved.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your assignment submission.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    public function storeFeedbackFile(Request $request, Assignment $assignment)
    {

dd($request->all());
        $response['type'] = 'error';
      /*  $assignment_id = $request->assignmentId;
        $authorized = Gate::inspect('uploadAssignmentFile', [$assignmentFile, $assignment->find($assignment_id)]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
      */

        try {
            //validator put here because I wasn't using vform so had to manually handle errors

            //wait 30 seconds between uploads
            //no more than 10 uploads per assignment
            //delete the file if there was an exception???

            $validator = Validator::make($request->all(), [
                'feedbackFile' => ['required', 'mimes:pdf', 'max:500000']
            ]);

            if ($validator->fails()) {
                $response['message'] = $validator->errors()->first('feedbackFile');
                return $response;
            }

            //save locally and to S3
            $feedbackFile = $request->file('feedbackFile')->store("feedbacks/$assignment_id", 'local');
            $feedbackContents = Storage::disk('local')->get($feedback);
            Storage::disk('s3')->put("feedbacks/$assignment_id/$feedback",  $feedbackContents);

            $assignmentFile->update(
                ['user_id' => Auth::user()->id, 'assignment_id' => $assignment_id],
                ['feedback_file' => basename($feedbackFile)]
            );
            $response['type'] = 'success';
            $response['message'] = 'Your feedback file has been saved.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your feedback file.  Please try again or contact us for assistance.";
        }
        return $response;

    }
    /**
     * Display the specified resource.
     *
     * @param \App\Upload $upload
     * @return \Illuminate\Http\Response
     */
    public function show(Upload $upload)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Upload $upload
     * @return \Illuminate\Http\Response
     */
    public function edit(Upload $upload)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Upload $upload
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Upload $upload)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Upload $upload
     * @return \Illuminate\Http\Response
     */
    public function destroy(Upload $upload)
    {
        //
    }
}
