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
        $authorized = Gate::inspect('setAsSolutionOrSubmission',[ $cutup, $assignment, $question]);

         if (!$authorized->allowed()) {
             $response['message'] = $authorized->message();
             return $response;
         }
        try {
            DB::beginTransaction();
            $page_number_and_extension = explode('_', $cutup->file)[1];
            $original_filename = 'filename';
            switch ($type) {
                case('solution'):
                    //add the new full solution
                    $original_filename = "solution-cutup-pg-$page_number_and_extension";
                    $solution->updateOrCreate(
                        ['user_id' => $user_id,
                            'question_id' => $question->id,
                            'type' => 'q'],
                        ['file' => $cutup->file, 'original_filename' => $original_filename]
                    );
                    $response['message'] = 'Your cutup has been set as the solution.';

                    break;
                case('submission'):
                    $original_filename = $submissionFile->where('assignment_id', $assignment->id)
                                    ->where('user_id', Auth::user()->id)
                                    ->where('type','a')
                                    ->first()
                                    ->original_filename;

                    if ($submissionFile->isPastSubmissionFileGracePeriod($extension, $assignment)) {
                        $response['message'] = 'You cannot set this cutup as a solution since this assignment is past due.';
                        return $response;
                    }
                    $original_filename = basename($original_filename, '.pdf') . "-pg-$page_number_and_extension";
                    $submission_file_data = ['type' => 'q',
                        'submission' => $cutup->file,
                        'original_filename' =>   $original_filename ,
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

                    $response['submission'] = $cutup->file;
                    $response['date_submitted'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime(date('Y-m-d H:i:s'), Auth::user()->time_zone);
                    $response['message'] = 'Your cutup has been saved as your file submission for this question.';
                    break;

            }
            Cutup::where('id', $cutup->id)->delete();
            $response['cutup'] = $original_filename;
            $response['type'] = 'success';

            DB::commit();


        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error setting this cutup as your solution.  Please try again or contact us for assistance.";
        }
        return $response;


    }

}
