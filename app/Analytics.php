<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Exceptions\InvalidSignatureException;
use MiladRahimi\Jwt\Exceptions\InvalidTokenException;
use MiladRahimi\Jwt\Exceptions\JsonDecodingException;
use MiladRahimi\Jwt\Exceptions\SigningException;
use MiladRahimi\Jwt\Exceptions\ValidationException;
use MiladRahimi\Jwt\Parser;

class Analytics extends Model
{
    /**
     * @param Request $request
     * @return array
     * @throws InvalidSignatureException
     * @throws InvalidTokenException
     * @throws JsonDecodingException
     * @throws SigningException
     * @throws ValidationException
     * @throws Exception
     */
    public function hasAccess(Request $request): array
    {
        if (!$request->bearerToken()) {
            throw new Exception ('Missing Bearer Token.');
        }
        $token = $request->bearerToken();
        $key = new HmacKey(config('myconfig.analytics_token'));
        $signer = new HS256($key);
        $parser = new Parser($signer);
        return $parser->parse($token);
    }
}
