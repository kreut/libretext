<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WhitelistedDomain extends Model
{
    protected $guarded = [];

    public function getWhitelistedDomainFromEmail(string $email)
    {
        if (strpos($email, '@') === false) {
            return $email;
        }
        $email_array = explode('@', $email);
        return array_pop($email_array);
    }
}
