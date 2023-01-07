<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Http\Requests\UpdatePatientInformation;
use App\PatientInformation;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PatientInformationController extends Controller
{
    /**
     * @param Assignment $assignment
     * @param PatientInformation $PatientInformation
     * @return array
     * @throws Exception
     */
    public function deleteUpdatedPatientInformation(Assignment $assignment, PatientInformation $PatientInformation): array
    {
        $response['type'] = 'error';
          $authorized = Gate::inspect('updateShowPatientUpdatedInformation', [$PatientInformation, $assignment]);

          if (!$authorized->allowed()) {
              $response['message'] = $authorized->message();
              return $response;
          }

        try {
            DB::table('patient_informations')
                ->where('assignment_id', $assignment->id)
                ->update(['updated_bmi' => null, 'updated_weight' => null, 'first_application_of_updated_information' => null]);
            $response['type'] = 'info';
            $response['message'] = 'The updated Patient Information has been removed.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to delete the updated Patient Information.  Please try again or contact us for assistance.";

        }
        return $response;

    }

    /**
     * @param PatientInformation $PatientInformation
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function updateShowPatientUpdatedInformation(PatientInformation $PatientInformation, Assignment $assignment): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateShowPatientUpdatedInformation', [$PatientInformation, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $patientInformation = $PatientInformation->where('assignment_id', $assignment->id)->first();
        if (!$patientInformation) {
            $response['type'] = 'error';
            $response['message'] = 'Please first save the initial Patient Information before adding the updated information.';
            return $response;
        }
        try {
            if ($patientInformation->show_in_updated_information) {
                $type = 'info';
                $message = "The updated patient information has been removed.";
                $patientInformation->updated_bmi = null;
                $patientInformation->updated_weight = null;
            } else {
                $type = 'info';
                $message = "The updated patient information has been added.";
            }

            $patientInformation->show_in_updated_information = !$patientInformation->show_in_updated_information;
            $patientInformation->update();
            $response['message'] = $message;
            $response['type'] = $type;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to update whether to show the updated Patient Information.  Please try again or contact us for assistance.";

        }
        return $response;

    }

    /**
     * @param Assignment $assignment
     * @param PatientInformation $patientInformation
     * @return array
     * @throws Exception
     */
    public function show(Assignment $assignment, PatientInformation $patientInformation): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('show', [$patientInformation, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $response['type'] = 'success';
            $response['patient_information'] = $patientInformation->where('assignment_id', $assignment->id)->first();
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get the patient information.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param UpdatePatientInformation $request
     * @param Assignment $assignment
     * @param PatientInformation $patientInformation
     * @return array
     * @throws Exception
     */
    public
    function update(UpdatePatientInformation $request,
                    Assignment               $assignment,
                    PatientInformation       $patientInformation): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$patientInformation, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $data = $request->validated();
            $data['assignment_id'] = $assignment->id;
            $data['updated_bmi'] = $request->updated_bmi;
            $data['updated_weight'] = $request->updated_weight;
            PatientInformation::updateOrCreate(['assignment_id' => $assignment->id], $data);
            $response['type'] = 'success';
            $response['message'] = "The patient information has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to update the patient information.  Please try again or contact us for assistance.";
        }
        return $response;


    }
}
