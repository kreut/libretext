<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LtiRegistration extends Model
{
    public static function isBrightSpace($iss)
    {
        return (str_contains($iss, 'brightspace')
            || str_contains($iss, 'desire2learn')
            || str_contains($iss, 'd2l')
            || str_contains($iss, 'online.pcc.edu'));
    }
}
