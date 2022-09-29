<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Http\Requests\UpdatePatientInformation;
use App\PatientInformation;
use Exception;
use Illuminate\Support\Facades\Gate;

class PatientInformationController extends Controller
{
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
