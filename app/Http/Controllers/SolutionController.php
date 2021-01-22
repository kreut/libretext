<?php

namespace App\Http\Controllers;


use App\Http\Requests\StoreTextSolution;
use App\Question;
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
    public function storeText(StoreTextSolution $request, Solution $Solution, Assignment $assignment, Question $question)
    {
        $response['type'] = 'error';
        $user_id = Auth::user()->id;
        try {

            $authorized = Gate::inspect('storeText', [$Solution, $assignment, $question]);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $Solution->where('user_id',$user_id)
                ->where('question_id',$question->id)
                ->update(['text'=>$request->text_solution]);

            $response['type'] = 'success';
            $response['message'] = 'Your text solution has been saved.';

        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving this audio solution.  Please try again or contact us for assistance.";
            return $response;
        }
        return $response;

    }
    public function storeAudioSolutionFile(Request $request, Solution $Solution, Assignment $assignment, Question $question, Cutup $cutup)
    {
        $response['type'] = 'error';
        $user_id = Auth::user()->id;
        try {

            $authorized = Gate::inspect('uploadSolutionFile', [$Solution, $assignment, $question]);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $validator = Validator::make($request->all(), [
                "audio" => $this->audioFileValidator()
            ]);

            if ($validator->fails()) {
                $response['message'] = $validator->errors()->first('audio');
                return $response;
            }

            $file = $request->file("audio")->store("solutions/$user_id", 'local');
            $solutionContents = Storage::disk('local')->get($file);
            Storage::disk('s3')->put($file, $solutionContents, ['StorageClass' => 'STANDARD_IA']);
            $original_filename = "Solution.mpg";
            $basename = basename($file);
            $file_data = [
                'file' => $basename,
                'original_filename' => $original_filename,
                'updated_at' => Carbon::now()];

            DB::beginTransaction();
            //if there's an upload PDF/IMG get rid of it.
            $Solution->where('question_id', $question->id)
                ->where('type', 'q')
                ->where('user_id', $user_id)
                ->delete();
            $cutup->forcePDFRecompileSolutionsByAssignment($assignment->id, $user_id, $Solution);
            $Solution->updateOrCreate(
                [
                    'user_id' => $user_id,
                    'type' => 'audio',
                    'question_id' => $question->id
                ],
                $file_data
            );
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'Your audio solution has been saved.';
            $response['solution'] = $original_filename;
            $response['solution_file_url'] =\Storage::disk('s3')->temporaryUrl("solutions/{$assignment->course->user_id}/$basename", now()->addMinutes(360));

        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving this audio solution.  Please try again or contact us for assistance.";
            return $response;
        }
        return $response;

    }

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
            $basename = basename($file);
            $file_data = [
                'file' => $basename,
                'original_filename' => $original_filename,
                'updated_at' => Carbon::now()];
            DB::beginTransaction();
            switch ($request->uploadLevel) {
                case('question'):
                    $assignment_name = Assignment::find($assignment_id)->name;
                    //if there's an upload audio get rid of it
                    $Solution->where('question_id', $question_id)
                        ->where('type', 'audio')
                        ->where('user_id', $user_id)
                        ->delete();
                    $Solution->updateOrCreate(
                        [
                            'user_id' => $user_id,
                            'type' => 'q',
                            'question_id' => $question_id
                        ],
                        $file_data
                    );
                    //now recompile with the new file
                    $compiled_filename = $cutup->forcePDFRecompileSolutionsByAssignment($assignment_id, $user_id, $Solution);
                    if ($compiled_filename) {
                        $compiled_file_data = [
                            'file' => $compiled_filename,
                            'original_filename' => str_replace(' ', '', $assignment_name . '.pdf'),
                            'updated_at' => Carbon::now()];
                        $Solution->updateOrCreate(
                            [
                                'user_id' => $user_id,
                                'type' => 'a',
                                'assignment_id' => $assignment_id,
                                'question_id' => null
                            ],
                            $compiled_file_data
                        );
                    }
                    $response['type'] = 'success';
                    $response['message'] = 'Your solution has been saved and the full answer key has been re-compiled.';
                    $response['original_filename'] = $original_filename;
                    $response['solution_file_url'] =\Storage::disk('s3')->temporaryUrl("solutions/{$user_id}/$basename", now()->addMinutes(360));
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
                    $cutup->cutUpPdf($file, "solutions/$user_id", $assignment_id, $user_id);


                    $response['type'] = 'success';
                    $response['message'] = 'Your PDF has been cutup into questions by page.';
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
            $authorized = Gate::inspect('downloadSolutionFile', [$solution, $level, $assignment, $request->question_id]);
            if (!$authorized->allowed()) {
                //I don't actually return a message to the user if they're not authorized, I just log it
                //for testing purposes I want to know why they weren't authorized
                if (env('DB_DATABASE') === "test_libretext") {
                    return ['message' => $authorized->message()];
                }
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
