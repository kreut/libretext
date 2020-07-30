<?php

namespace App\Policies;

use App\Score;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScorePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the score.
     *
     * @param  \App\User  $user
     * @param  \App\Score  $score
     * @return mixed
     */
    public function update(User $user,  Score $score)
    {

    }

}
