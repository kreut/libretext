<?php

namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Packback\Lti1p3\Interfaces\ICookie;

class Lti13Cookie implements ICookie
{
    public function getCookie(string $name): ?string
    {
        return Cookie::get( $name, false);
    }

    public function setCookie(string $name, string $value, $exp = 3600, $options = []): void
    {
        Cache::put($name, $value, 60);

    }
}
