<?php

namespace App\Http\Controllers;


use App\Solution;
use App\Assignment;
use App\Cutup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\S3;


class SolutionController extends Controller
{

    use S3;

    public function storeSolutionFile(Request $request, Solution $Solution, Cutup $cutup)
    {


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
            $assignment_id = $request->assignmentId;
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
            DB::beginTransaction();
            switch ($request->uploadLevel) {
                case('question'):
                    $Solution->updateOrCreate(
                        [
                            'user_id' => $user_id,
                            'type' => 'q',
                            'question_id' => $question_id
                        ],
                        $file_data
                    );
                    $response['type'] = 'success';
                    $response['message'] = 'Your solution has been saved.';
                    $response['original_filename'] = $original_filename;
                    break;
                case('assignment'):

                    //get rid of the current ones
                    Cutup::where('user_id', $user_id)
                        ->where('assignment_id', $assignment_id)
                        ->delete();

                    //add the new full solution
                    $Solution->updateOrCreate(
                        ['user_id' => $user_id,
                            'assignment_id' => $assignment_id,
                            'type' => 'a'],
                        $file_data
                    );

                    //add the cutups
                    $this->cutUpPdf($file, "solutions/$user_id", $cutup, $assignment_id, $user_id);


                    $response['type'] = 'success';
                    $response['message'] = 'Your pdf has been cutup into questions by page.';
                    $response['original_filename'] = $original_filename;
                    break;
                default:
                    $response['message'] = 'That is not a valid upload level.  Please contact us for assistance.';

            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving this solution.  Please try again or contact us for assistance.";
            return $response;
        }
        return $response;

    }

    public function downloadSolutionFile(Request $request, Solution $solution)
    {
        $response['type'] = 'error';

        //person who created the file
        $assignment = Assignment::find($request->assignment_id);
        $level = $request->level;
        try {
         $authorized = Gate::inspect('downloadSolutionFile', [$solution, $level,  $assignment,$request->question_id]);
         if (!$authorized->allowed()) {
             throw new Exception($authorized->message());
         }

        $file_creator_user_id = $assignment->course->user_id;

            $solution_file = ($level === 'q')
                ? $solution->where('user_id', $file_creator_user_id)
                    ->where('question_id', $request->question_id)
                    ->first()->file
                : $solution->where('user_id', $file_creator_user_id)
                    ->where('assignment_id', $request->assignment_id)
                    ->first()->file;

            return Storage::disk('s3')->download("solutions/$file_creator_user_id/$solution_file");
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
