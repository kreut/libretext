<?php

namespace App\Http\Controllers;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256CBCHS512;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\Encryption\Serializer\JWESerializerManager;


use Jose\Component\Encryption\JWEDecrypter;

use Jose\Easy\Load;

class JWE2Controller extends Controller
{
    public function encode()
    {
        // The key encryption algorithm manager with the A256KW algorithm.
        $keyEncryptionAlgorithmManager = new AlgorithmManager([
            new A256KW(),
        ]);

// The content encryption algorithm manager with the A256CBC-HS256 algorithm.
        $contentEncryptionAlgorithmManager = new AlgorithmManager([
            new A256CBCHS512(),
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

        //Our key
        $jwk = new JWK([
            'kty' => 'oct',
            'k' => 'dzI6nbW4OcNF-AtfxGAmuyz7IpHRudBI0WgGjZWgaRJt6prBn3DARXgUR8NVwKhfL43QBIU2Un3AvCGCHRgY4TbEqhOi8-i98xxmCggNjde4oaW6wkJ2NgM3Ss9SOX9zS3lcVzdCMdum-RwVJ301kbin4UtGztuzJBeg5oVN00MGxjC2xWwyI0tgXVs-zJs5WlafCuGfX1HrVkIf5bvpE0MQCSjdJpSeVao6-RSTYDajZf7T88a2eVjeW31mMAg-jzAWfUrii61T_bYPJFOXW8kkRWoa1InLRdG6bKB9wQs9-VdXZP60Q4Yuj_WZ-lO7qV9AEFrUkkjpaDgZT86w2g',
        ]);

// The payload we want to encrypt. It MUST be a string.
        $payload = json_encode([
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
            'iss' => 'My service',
            'aud' => 'Your application',
        ]);

        $jwe = $jweBuilder
            ->create()              // We want to create a new JWE
            ->withPayload($payload) // We set the payload
            ->withSharedProtectedHeader([
                'alg' => 'A256KW',        // Key Encryption Algorithm
                'enc' => 'A256CBC-HS512', // Content Encryption Algorithm
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
            new A256KW(),
        ]);

// The content encryption algorithm manager with the A256CBC-HS256 algorithm.
        $contentEncryptionAlgorithmManager = new AlgorithmManager([
            new A256CBCHS512(),
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

        // Our key.
        $jwk = new JWK([
            'kty' => 'oct',
            'k' => 'dzI6nbW4OcNF-AtfxGAmuyz7IpHRudBI0WgGjZWgaRJt6prBn3DARXgUR8NVwKhfL43QBIU2Un3AvCGCHRgY4TbEqhOi8-i98xxmCggNjde4oaW6wkJ2NgM3Ss9SOX9zS3lcVzdCMdum-RwVJ301kbin4UtGztuzJBeg5oVN00MGxjC2xWwyI0tgXVs-zJs5WlafCuGfX1HrVkIf5bvpE0MQCSjdJpSeVao6-RSTYDajZf7T88a2eVjeW31mMAg-jzAWfUrii61T_bYPJFOXW8kkRWoa1InLRdG6bKB9wQs9-VdXZP60Q4Yuj_WZ-lO7qV9AEFrUkkjpaDgZT86w2g',
        ]);

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

        echo "\r\n" . $jwe->getPayload();

    }


}
