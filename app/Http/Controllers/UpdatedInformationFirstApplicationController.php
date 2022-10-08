<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\CaseStudyNote;
use App\Exceptions\Handler;
use App\PatientInformation;
use App\UpdatedInformationFirstApplication;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdatedInformationFirstApplicationController extends Controller
{
    public function index(Assignment $assignment)
    {
        $response['type'] = 'error';
        /*  $authorized = Gate::inspect('updateStudentEmail', [$request->user(), $student->id]);
          if (!$authorized->allowed()) {
              $response['message'] = $authorized->message();
              return $response;
          }*/
        try {
            $questions = DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->select('title', 'order')
                ->orderBy('order')
                ->where('assignment_id', $assignment->id)
                ->get();
            $first_applications = DB::table('case_study_notes')
                ->where('assignment_id', $assignment->id)
                ->where('version', 1)
                ->get();
            $response['questions'] = $questions;
            $response['first_applications'] = $first_applications;
            $response['type'] = 'success';


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the first applications. Please try again or contact us.";

        }
        return $response;

    }

    public function update(Request $request)
    {
        $response['type'] = 'error';
        /*  $authorized = Gate::inspect('updateStudentEmail', [$request->user(), $student->id]);
          if (!$authorized->allowed()) {
              $response['message'] = $authorized->message();
              return $response;
          }*/
        try {
            switch ($request->type) {
                case('case_study_notes'):
                    CaseStudyNote::find($request->case_study_notes_id)->update(['first_application' => $request->first_application]);
                    break;
                case('patient_information'):
                    DB::table('patient_informations')->where('assignment_id', $request->assignment_id)
                        ->update(['first_application_of_updated_information' => $request->first_application]);
            }


            $response['type'] = 'success';
            $response['message'] = "The first application has been updated.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the first applications. Please try again or contact us.";

        }
        return $response;

    }
}
