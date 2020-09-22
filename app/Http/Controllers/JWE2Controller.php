<?php

namespace App\Http\Controllers;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A128KW;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A128CBCHS256;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\KeyManagement\JWKFactory;




use Jose\Component\Encryption\JWEDecrypter;

use Jose\Easy\Load;

class JWE2Controller extends Controller
{
    public function encode()
    {


        // The key encryption algorithm manager with the A256KW algorithm.
        $keyEncryptionAlgorithmManager = new AlgorithmManager([
            new RSAOAEP(),
        ]);

// The content encryption algorithm manager with the A256CBC-HS256 algorithm.
        $contentEncryptionAlgorithmManager = new AlgorithmManager([
            new A256GCM(), //A256GCM
        ]);

// The compression method manager with the DEF (Deflate) method.
        $compressionMethodManager = new CompressionMethodManager([
            new Deflate(),
        ]);

// We instantiate our JWE Builder.
        $jweBuilder = new JWEBuilder(
            $keyEncryptionAlgorithmManager,
            $contentEncryptionAlgorithmManager,
            $compressionMethodManager
        );

       //webwork-rsa.pub to encode
        $jwk = JWKFactory::createRSAKey(
            4096, // Size in bits of the key. We recommend at least 2048 bits.
            [
                'alg' => 'RSA-OAEP', // This key must only be used with the RSA-OAEP-256 algorithm
                'use' => 'enc'    // This key is used for encryption/decryption operations only
            ]);

        //Our key


// The payload we want to encrypt. It MUST be a string.
        $payload = json_encode([
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600, //TODO: shorten
            'iss' => 'My service',
            'aud' => 'Your application',
        ]);



        $jwe = $jweBuilder
            ->create()              // We want to create a new JWE
            ->withPayload($payload) // We set the payload
            ->withSharedProtectedHeader([
                'alg' => 'RSA-OAEP',        // Key Encryption --- Algorithm RSA-OAEP
                'enc' => 'A256GCM', // Content Encryption  --- Algorithm A256GCM
                'zip' => 'DEF'            // We enable the compression (irrelevant as the payload is small, just for the example).
            ])
            ->addRecipient($jwk)    // We add a recipient (a shared key or public key).
            ->build();              // We build it

        $serializer = new CompactSerializer(); // The serializer

        $token = $serializer->serialize($jwe, 0); // We serialize the recipient at index 0 (we only have one recipient).
//dd($token);
echo "Encrypted token: $token\r\n\r\n";

        // The key encryption algorithm manager with the A256KW algorithm.
        $keyEncryptionAlgorithmManager = new AlgorithmManager([
            new RSAOAEP(),
        ]);


// The content encryption algorithm manager with the A256CBC-HS256 algorithm.
        $contentEncryptionAlgorithmManager = new AlgorithmManager([
            new A256GCM(),
        ]);

// The compression method manager with the DEF (Deflate) method.
        $compressionMethodManager = new CompressionMethodManager([
            new Deflate(),
        ]);

// We instantiate our JWE Decrypter.
        $jweDecrypter = new JWEDecrypter(
            $keyEncryptionAlgorithmManager,
            $contentEncryptionAlgorithmManager,
            $compressionMethodManager
        );



// The serializer manager. We only use the JWE Compact Serialization Mode.
        $serializerManager = new JWESerializerManager([
            new CompactSerializer(),
        ]);

// The input we want to decrypt
        //  $token = 'eyJhbGciOiJBMjU2S1ciLCJlbmMiOiJBMjU2Q0JDLUhTNTEyIiwiemlwIjoiREVGIn0.9RLpf3Gauf05QPNCMzPcH4XNBLmH0s3e-YWwOe57MTG844gnc-g2ywfXt_R0Q9qsR6WhkmQEhdLk2CBvfqr4ob4jFlvJK0yW.CCvfoTKO9tQlzCvbAuFAJg.PxrDlsbSRcxC5SuEJ84i9E9_R3tCyDQsEPTIllSCVxVcHiPOC2EdDlvUwYvznirYP6KMTdKMgLqxB4BwI3CWtys0fceSNxrEIu_uv1WhzJg.4DnyeLEAfB4I8Eq0UobnP8ymlX1UIfSSADaJCXr3RlU';

// We try to load the token.
        $jwe = $serializerManager->unserialize($token);

// We decrypt the token. This method does NOT check the header.
        $success = $jweDecrypter->decryptUsingKey($jwe, $jwk, 0);
echo  $success . 'jere';
        echo "\r\n" . $jwe->getPayload();

    }


}
