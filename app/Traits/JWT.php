<?php


namespace App\Traits;


use Exception;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;


trait JWT
{

    /**
     * Verifies an HS256-signed JWT and returns its claims as an object.
     * Replaces Jose\Easy\Load::jws(), which does not exist in web-token/jwt-library.
     *
     * @throws Exception if the token is malformed, not HS256, or the signature is invalid
     */
    public function getPayload($token, $secret)
    {
        $jwk = JWKFactory::createFromSecret($secret);

        $jws = (new CompactSerializer())->unserialize($token);

        // Only HS256 is allowed, as before (->algs(['HS256']))
        $header = $jws->getSignature(0)->getProtectedHeader();
        if (($header['alg'] ?? null) !== 'HS256') {
            throw new Exception('Unsupported JWT algorithm: ' . ($header['alg'] ?? 'none'));
        }

        $verifier = new JWSVerifier(new AlgorithmManager([new HS256()]));
        if (!$verifier->verifyWithKey($jws, $jwk, 0)) {
            throw new Exception('Invalid JWT signature.');
        }

        // Same shape as before: top-level object, nested claims as arrays
        $claims = json_decode($jws->getPayload(), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($claims)) {
            throw new Exception('JWT payload is not a JSON object.');
        }

        return (object)$claims;
    }

}
