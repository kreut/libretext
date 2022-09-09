<?php

namespace App;

use Illuminate\Support\Facades\Cookie;
use Packback\Lti1p3\Interfaces\ICookie;

class Lti13Cookie implements ICookie
{
    public function getCookie(string $name): ?string
    {
        return Cookie::get($name, false);
    }

    public function setCookie(string $name, string $value, $exp = 3600, $options = []): void
    {
        // By default, make the cookie expire within a minute
        Cookie::queue($name, $value, $exp / 60);
    }
}
