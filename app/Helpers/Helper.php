<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class Helper
{
    public static function isAdmin(): bool
    {
        return Auth::user() && in_array(Auth::user()->id, [1, 5]);

    }
    public static function isAnonymousUser(): bool {
        return Auth::user() && Auth::user()->email === 'anonymous';
    }

    public static function removeZerosAfterDecimal($num)
    {
        $pos = strpos($num, '.');
        if ($pos === false) { // it is integer number
            return $num;
        } else { // it is decimal number
            return rtrim(rtrim($num, '0'), '.');
        }
    }


}
