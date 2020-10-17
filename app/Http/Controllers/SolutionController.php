<?php

namespace App\Http\Controllers;


use App\Solution;
use App\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\S3;

class SolutionController extends Controller
{

    use S3;

    public function storeSolutionFile(Request $request, Solution $Solution)
    {

/**
    If it's a cutup...
    1. Save the solution file as a whole file with assignment as the row (need this for students later)
    2. Do the cutup and add those to the cutup database
    3. Allow them to do the cutups

    4. If a student, do the cut up right off the bat and save to the cutup database then let them add.
**/
        $response['type'] = 'error';

        try {

            $authorized = Gate::inspect('uploadSolutionFile', $Solution);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $validator = Validator::make($request->all(), [
                "solutionFile" => $this->fileValidator()
            ]);

            if ($validator->fails()) {
                $response['message'] = $validator->errors()->first('solutionFile');
                return $response;
            }
        $question_id = $request->questionId;
        $user_id = Auth::user()->id;
        $file = $request->file("solutionFile")->store("solutions/$user_id", 'local');

        $solutionContents = Storage::disk('local')->get($file);

        Storage::disk('s3')->put($file, $solutionContents, ['StorageClass' => 'STANDARD_IA']);
        $original_filename = $request->file("solutionFile")->getClientOriginalName();
        $file_data = [
            'file' => basename($file),
            'original_filename' => $original_filename,
            'updated_at' => Carbon::now()];
        $Solution->updateOrCreate(
            ['user_id' => $user_id,
                'question_id' => $question_id],
            $file_data
        );
        $response['type'] = 'success';
        $response['message'] = 'Your solution has been saved.';
        $response['original_filename'] = $original_filename;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return null;
        }
        return $response;

    }

    public function downloadSolutionFile(Request $request, Solution $solution)
    {
        $response['type'] = 'error';

        //person who created the file
        $assignment = Assignment::find($request->assignment_id);


        $authorized = Gate::inspect('downloadSolutionFile', [$solution, $request->question_id, $assignment]);
        if (!$authorized->allowed()) {
            throw new Exception($authorized->message());
        }

        $file_creator_user_id = $assignment->course->user_id;
        try {
            $solution_file = $solution->where('user_id', $file_creator_user_id)
            ->where('question_id', $request->question_id)
            ->first()->file;

            return Storage::disk('s3')->download("solutions/$file_creator_user_id/$solution_file");
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return null;
        }
    }
}
