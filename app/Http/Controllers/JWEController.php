<?php

namespace App\Http\Controllers;

use Jose\Factory\JWEFactory;
use Jose\Factory\JWKFactory;


use Jose\Loader;
class JWEController extends Controller
{
    public function encode()
    {

//https://github.com/Spomky-Labs/jose
        // We create our key object (JWK) using a public RSA key stored in a file
// Additional parameters ('kid' and 'use') are set for this key.
        $key = JWKFactory::createFromKeyFile(
            app_path() . '/../JWT-Protected/public_key',
            null,
            [
                'kid' => 'My Public RSA key',
                'use' => 'enc',
                'alg' => 'RSA-OAEP-256',
            ]
        );

// We want to encrypt a very important message
        $input = JWEFactory::createJWEToCompactJSON(
            '8:00PM, train station',
            $key,
            [
                'alg' => 'RSA-OAEP-256',
                'enc' => 'A256CBC-HS512',
                'zip' => 'DEF',
            ]
        );

echo "Encrypted input: " . $input . "\r\n";
        // To decrypt and verify our JWE, we need a JWK (or JWKSet) that contains the private key(s).
// We create our key object (JWK) using an encrypted RSA key stored in a file.
// The key is encrypted and the second argument is the password.
// Additional parameters ('kid' and 'use') are set for this key.
        $key = JWKFactory::createFromKeyFile(
            app_path() . '/../JWT-Protected/private_encrypted_key',
            'tests',
            [
                'kid' => 'My private RSA key',
                'use' => 'enc',
            ]
        );

        /******************/
        /*    LET'S GO!   */
        /******************/

// We load the input and we try to decrypt it.
// The first argument is our input
// The second argument is our private key
// The third argument is a list of allowed algorithms.
        $loader = new Loader();
        $jwe = $loader->loadAndDecryptUsingKey(
            $input,
            $key,
            ['RSA-OAEP-256'],
            ['A256CBC-HS512']
        );
dd($jwe);

    }
}
