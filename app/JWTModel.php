<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
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

class JWTModel extends Model
{

    protected $keyEncryptionAlgorithmManager;
    protected $contentEncryptionAlgorithmManager;
    protected $compressionMethodManager;
    protected $jwk;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // The key encryption algorithm manager with the A256KW algorithm.
        $this->keyEncryptionAlgorithmManager = new AlgorithmManager([
            new A256KW(),
        ]);
        // The content encryption algorithm manager with the A256CBC-HS256 algorithm.
        $this->contentEncryptionAlgorithmManager = new AlgorithmManager([
            new A256CBCHS512(),
        ]);

        // The compression method manager with the DEF (Deflate) method.
        $this->compressionMethodManager = new CompressionMethodManager([
            new Deflate(),
        ]);

        $this->jwk = new JWK([
            'kty' => 'oct',
            'k' => 'webwork', //TODO: load from my .env file so it's out of version control
        ]);


    }

    public function init()
    {
        $token = $this->encode('My really secret payload that only Henry knows.');
        $this->decode($token);
    }


    public function encode($payload)
    {

// We instantiate our JWE Builder.
        $jweBuilder = new JWEBuilder(
            $this->keyEncryptionAlgorithmManager,
            $this->contentEncryptionAlgorithmManager,
            $this->compressionMethodManager
        );


        $jwe = $jweBuilder
            ->create()              // We want to create a new JWE
            ->withPayload($payload) // We set the payload
            ->withSharedProtectedHeader([
                'alg' => 'A256KW',        // Key Encryption Algorithm
                'enc' => 'A256CBC-HS512', // Content Encryption Algorithm
                'zip' => 'DEF'            // We enable the compression (irrelevant as the payload is small, just for the example).
            ])
            ->addRecipient($this->jwk)    // We add a recipient (a shared key or public key).
            ->build();              // We build it

        $serializer = new CompactSerializer(); // The serializer

        $token = $serializer->serialize($jwe, 0); // We serialize the recipient at index 0 (we only have one recipient).
        //echo "Encrypted token: $token\r\n\r\n";
        return $token;
    }

    public function decode(string $token)
    {
// We instantiate our JWE Decrypter.
        $jweDecrypter = new JWEDecrypter(
            $this->keyEncryptionAlgorithmManager,
            $this->contentEncryptionAlgorithmManager,
            $this->compressionMethodManager
        );


// The serializer manager. We only use the JWE Compact Serialization Mode.
        $serializerManager = new JWESerializerManager([
            new CompactSerializer(),
        ]);

// We try to load the token.
        $jwe = $serializerManager->unserialize($token);

// We decrypt the token. This method does NOT check the header.
        $success = $jweDecrypter->decryptUsingKey($jwe, $this->jwk, 0);
        return $jwe->getPayload();
    }
}
