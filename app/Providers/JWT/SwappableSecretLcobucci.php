<?php

namespace App\Providers\JWT;

use Tymon\JWTAuth\Providers\JWT\Lcobucci;

/**
 * tymon/jwt-auth 1.x read the secret each time it signed or verified a token, so
 * calling setSecret() on the provider switched keys immediately. Version 2.x builds
 * its lcobucci Configuration (signer + key) once in the constructor, so setSecret()
 * no longer has any effect on encode() or decode().
 *
 * ADAPT relies on swapping secrets (e.g. signing WeBWorK problem JWTs with the
 * WeBWorK secret, then switching back), so this subclass rebuilds the
 * Configuration whenever the secret changes.
 */
class SwappableSecretLcobucci extends Lcobucci
{
    /**
     * @param string $secret
     * @return $this
     */
    public function setSecret($secret)
    {
        parent::setSecret($secret);
        $this->config = $this->buildConfig();

        return $this;
    }
}
