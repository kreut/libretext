<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\Algorithm\KeyEncryption\PBES2HS512A256KW;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\KeyManagement\JWKFactory;


class JWE extends Model
{
    public $keyEncryptionAlgorithmManager;
    public $contentEncryptionAlgorithmManager;
    public $compressionMethodManager;
    public $jwk;
    public $serializer;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->keyEncryptionAlgorithmManager = new AlgorithmManager([new PBES2HS512A256KW(),]);
        $this->contentEncryptionAlgorithmManager = new AlgorithmManager([new A256GCM(),]);
        $this->compressionMethodManager = new CompressionMethodManager([new Deflate(),]);

        $this->jwk = JWKFactory::createFromSecret(
            'secretkey'      // The shared secret
        );
        $this->serializer = new CompactSerializer(); // The serializer
    }

    public function encrypt(string $jwt)
    {
        $jweBuilder = new JWEBuilder(
            $this->keyEncryptionAlgorithmManager,
            $this->contentEncryptionAlgorithmManager,
            $this->compressionMethodManager
        );

        $jwe = $jweBuilder
            ->create()              // We want to create a new JWE
            ->withPayload($jwt) // We set the payload
            ->withSharedProtectedHeader(['alg' => 'PBES2-HS512+A256KW',        // Key Encryption Algorithm
                'enc' => 'A256GCM', // Content Encryption Algorithm
                'zip' => 'DEF'])            // We enable the compression (irrelevant as the payload is small, just for the example).])
            ->addRecipient($this->jwk)    // We add a recipient (a shared key or public key).
            ->build();              // We build it

        return  $this->serializer->serialize($jwe, 0); // We serialize the recipient at index 0 (we only have one recipient).
    }

    public function decrypt(string $token)
    {

        $jweDecrypter = new JWEDecrypter(
            $this->keyEncryptionAlgorithmManager,
            $this->contentEncryptionAlgorithmManager,
            $this->compressionMethodManager
        );
        $jwe = $this->serializer->unserialize($token);
        $success = $jweDecrypter->decryptUsingKey($jwe, $this->jwk, 0);
        return $success ? $jwe->getPayload() : false;

    }

}
