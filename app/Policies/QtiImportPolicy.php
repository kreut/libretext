<?php

namespace App\Policies;

use App\QtiImport;
use App\QtiJob;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class QtiImportPolicy
{
    use HandlesAuthorization;

    public function store(User $user, QtiImport $qtiImport, QtiJob $qti_job): Response
    {
        return ($qti_job->user_id === $user->id)
            ? Response::allow()
            : Response::deny("This is not your QTI job.");

    }
}
