<?php

namespace App\Policies;

use App\Helpers\Helper;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class WebworkSubmissionErrorPolicy
{
    use HandlesAuthorization;

    /**
     * @return Response
     */
    public function submissionErrors(): Response
    {
        return Helper::isAdmin()
            ? Response::allow()
            : Response::deny('You are not allowed to get the webwork submission errors.');

    }
}
