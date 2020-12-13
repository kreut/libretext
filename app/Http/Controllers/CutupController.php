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


class CutupController extends Controller
{

    use S3;
    use DateFormatter;

    public function show(Request $request, Assignment $assignment, Cutup $cutup)
    {

        $user_id = Auth::user()->id;
        $response['type'] = 'error';
        /* $authorized = Gate::inspect('view', $enrollment);

         if (!$authorized->allowed()) {
             $response['message'] = $authorized->message();
             return $response;
         }*/
        try {
            $cutups = [];
            $results = $cutup->where('assignment_id', $assignment->id)
                ->where('user_id', $user_id)
                ->orderBy('id', 'asc')
                ->get();

            if ($results->isNotEmpty()) {
                foreach ($results as $key => $value) {
                    $dir = (Auth::user()->role == 2) ? "solutions/$user_id/"
                        : "assignments/$assignment->id/";
                    $cutups[] = [
                        'id' => $value->id,
                        'temporary_url' => \Storage::disk('s3')->temporaryUrl($dir . $value->file, now()->addMinutes(120))
                    ];
                }
            }
            $response['type'] = 'success';
            $response['cutups'] = $cutups;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your cutups.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function setAsSolutionOrSubmission(Request $request, Assignment $assignment, Question $question, Cutup $cutup, Solution $solution, SubmissionFile $submissionFile, Extension $extension)
    {


        $type = (Auth::user()->role === 2) ? 'solution' : 'submission';

        $user_id = Auth::user()->id;
        $response['type'] = 'error';
        $authorized = Gate::inspect('setAsSolutionOrSubmission', [$cutup, $assignment, $question]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        if ($type === 'submission') {
            if ($submissionFile->isPastSubmissionFileGracePeriod($extension, $assignment)) {
                $response['message'] = 'You cannot set this cutup as a solution since this assignment is past due.';
                return $response;
            }
        }

        $chosen_cutups = str_replace(' ', '', $request->chosen_cutups);
        $page_numbers_and_extension = $chosen_cutups . '.pdf';
        $chosen_cutups = explode(',',$chosen_cutups);

        try {
            $cutup_file = $cutup->mergeCutUpPdfs($submissionFile, $solution, $type, $assignment->id, $user_id, $chosen_cutups, $page_numbers_and_extension);
            DB::beginTransaction();
            switch ($type) {
                case('solution'):
                    //add the new full solution
                    $sanitized_name = preg_replace( '/[^a-z0-9]+/', '_', strtolower($assignment->name ));
                    $cutup_filename = $sanitized_name . "-q" . $request->question_num .".pdf";
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
                    $response['cutup'] = $cutup_filename;
                    break;
                case('submission'):
                    $original_filename = $submissionFile->where('assignment_id', $assignment->id)
                        ->where('user_id', Auth::user()->id)
                        ->where('type', 'a')
                        ->first()
                        ->original_filename;

                    $original_filename = basename($original_filename, '.pdf') . "-$page_numbers_and_extension";
                    $submission_file_data = ['type' => 'q',
                        'submission' => $cutup_file,
                        'original_filename' => $original_filename,
                        'file_feedback' => null,
                        'text_feedback' => null,
                        'date_graded' => null,
                        'score' => null,
                        'date_submitted' => Carbon::now()];
                    $submissionFile->updateOrCreate(
                        ['user_id' => Auth::user()->id,
                            'assignment_id' => $assignment->id,
                            'question_id' => $question->id,
                            'type' => 'q'],
                        $submission_file_data
                    );

                    $response['submission'] = $cutup_file;
                    $response['date_submitted'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime(date('Y-m-d H:i:s'), Auth::user()->time_zone);
                    $response['message'] = 'Your cutup has been saved as your file submission for this question.';
                    $response['cutup'] = $original_filename;
                    break;

            }

            $response['type'] = 'success';

            DB::commit();


        } catch (Exception $e) {
            DB::rollBack();
            if (strpos($e->getMessage(), 'Your cutups should be a comma') === false) {
                $h = new Handler(app());
                $h->report($e);
            }
            $response['message'] =  $e->getMessage();
        }
        return $response;


    }

}
