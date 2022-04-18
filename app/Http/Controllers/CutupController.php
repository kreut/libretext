<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Cutup;
use App\Question;
use App\Solution;
use App\Extension;
use App\SubmissionFile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Traits\S3;
use App\Traits\DateFormatter;
use App\Traits\GeneralSubmissionPolicy;
use App\Traits\LatePolicy;
use Illuminate\Support\Facades\Storage;


class CutupController extends Controller
{

    use S3;
    use DateFormatter;
    use GeneralSubmissionPolicy;
    use LatePolicy;

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Cutup $cutup
     * @return array
     * @throws Exception
     */
    public function show(Request $request, Assignment $assignment, Cutup $cutup)
    {

        $user_id = $request->user()->id;
        $response['type'] = 'error';
         $authorized = Gate::inspect('view', $cutup);

         if (!$authorized->allowed()) {
             $response['message'] = $authorized->message();
             return $response;
         }
        try {
            $cutups = [];
            $results = $cutup->where('assignment_id', $assignment->id)
                ->where('user_id', $user_id)
                ->orderBy('id', 'asc')
                ->get();

            if ($results->isNotEmpty()) {
                foreach ($results as $key => $value) {
                    $dir = "solutions/$user_id/";
                    $cutups[] = [
                        'id' => $value->id,
                        'temporary_url' => Storage::disk('s3')->temporaryUrl($dir . $value->file, now()->addMinutes(120))
                    ];
                }
            }

            $response['cutups'] = $cutups;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your cutups.  Please refresh your page and try again.";
        }
        return $response;

    }



    public function updateSolution(Request $request, Assignment $assignment, Question $question, Cutup $cutup, Solution $solution, SubmissionFile $submissionFile, Extension $extension)
    {

        $user = Auth::user();


        $user_id = $user->id;
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateSolution', [$cutup, $assignment, $question]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        $chosen_cutups = str_replace(' ', '', $request->chosen_cutups);
        $page_numbers_and_extension = $chosen_cutups . '.pdf';
        $chosen_cutups = explode(',', $chosen_cutups);

        try {
            $cutup_file = $cutup->mergeCutUpPdfs( $solution, $assignment->id, $user_id, $chosen_cutups, $page_numbers_and_extension);
            DB::beginTransaction();

            //add the new full solution
            $sanitized_name = preg_replace('/[^a-z0-9]+/', '_', strtolower($assignment->name));
            $cutup_filename = $sanitized_name . "-q" . $request->question_num . ".pdf";

            //if there's an audio one, then first get rid of it
            $solution->where('question_id', $question->id)
                ->where('type', 'audio')
                ->where('user_id', $user_id)
                ->delete();


            $solution->updateOrCreate(
                ['user_id' => $user_id,
                    'question_id' => $question->id,
                    'type' => 'q'],
                ['file' => $cutup_file, 'original_filename' => $cutup_filename]
            );
            //now recompile with the new file
            $compiled_filename = $cutup->forcePDFRecompileSolutionsByAssignment($assignment->id, $user_id, $solution);
            if ($compiled_filename) {
                $compiled_file_data = [
                    'file' => $compiled_filename,
                    'original_filename' => str_replace(' ', '', $assignment->name . '.pdf'),
                    'updated_at' => Carbon::now()];
                $solution->updateOrCreate(
                    [
                        'user_id' => $user_id,
                        'type' => 'a',
                        'assignment_id' => $assignment->id,
                        'question_id' => null
                    ],
                    $compiled_file_data
                );
            }
            $response['message'] = 'Your cutup has been set as the solution.';
            $response['solution_file_url'] = \Storage::disk('s3')->temporaryUrl("solutions/$user_id/$cutup_file", now()->addMinutes(120));
            $response['cutup'] = $cutup_filename;

            $response['type'] = 'success';

            DB::commit();


        } catch (Exception $e) {
            DB::rollBack();
            if (strpos($e->getMessage(), 'Your cutups should be a comma') === false) {
                $h = new Handler(app());
                $h->report($e);
            }
            $response['message'] = $e->getMessage();
        }
        return $response;


    }

}
