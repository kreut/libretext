<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Cutup;
use App\Question;
use App\Solution;
use Illuminate\Http\Request;
use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Traits\S3;
use Illuminate\Support\Facades\Storage;

class CutupController extends Controller
{

    use S3;

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
                        'temporary_url' => \Storage::disk('s3')->temporaryUrl( $dir .$value->file, now()->addMinutes(120))
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

    public function setAsSolution(Request $request, Question $question, Cutup $cutup, Solution $solution)
    {

        $user_id = Auth::user()->id;
        $response['type'] = 'error';
        /* $authorized = Gate::inspect('view', $enrollment);

         if (!$authorized->allowed()) {
             $response['message'] = $authorized->message();
             return $response;
         }*/
        try {
            DB::beginTransaction();
            $page_number_and_extension = explode('_',$cutup->file)[1];

            $original_filename =  "solution-cutup-$page_number_and_extension";
            //add the new full solution
            $solution->updateOrCreate(
                ['user_id' => $user_id,
                    'question_id' => $question->id,
                    'type' => 'q'],
                ['file' => $cutup->file, 'original_filename' =>   $original_filename]
            );

            Cutup::where('id', $cutup->id)->delete();

            $response['type'] = 'success';
            $response['message'] = 'Your cutup has been set as the solution.';
            $response['cutup'] =  $original_filename;

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
