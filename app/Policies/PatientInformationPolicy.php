<?php

namespace App\Policies;

use App\PatientInformation;
use App\User;
use App\Assignment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PatientInformationPolicy
{
    use HandlesAuthorization;


    public function destroy(User $user, PatientInformation $patientInformation, Assignment $assignment): Response
    {

        return ($assignment->course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to delete the Patient Information.');

    }


    /**
     * @param User $user
     * @param PatientInformation $patientInformation
     * @param Assignment $assignment
     * @return Response
     */
    public function updateShowPatientUpdatedInformation(User $user, PatientInformation $patientInformation, Assignment $assignment): Response
    {

        return ($assignment->course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to update whether to show the updated Patient Information.');

    }
    /**
     * @param User $user
     * @param PatientInformation $patientInformation
     * @param Assignment $assignment
     * @return Response
     */
    public function update(User $user, PatientInformation $patientInformation, Assignment $assignment): Response
    {

        return ($assignment->course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to update this patient information.');

    }

    public function show(User $user, PatientInformation $patientInformation, Assignment $assignment): Response
    {

        return ($assignment->course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to view this patient information.');

    }

}
