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
use App\Traits\JWT;
use Illuminate\Support\Facades\Log;

use App\Exceptions\Handler;
use \Exception;

class JWE extends Model

{
    public $keyEncryptionAlgorithmManager;
    public $contentEncryptionAlgorithmManager;
    public $compressionMethodManager;
    public $jwk;
    public $serializer;


    use JWT;
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->keyEncryptionAlgorithmManager = new AlgorithmManager([new PBES2HS512A256KW(),]);
        $this->contentEncryptionAlgorithmManager = new AlgorithmManager([new A256GCM(),]);
        $this->compressionMethodManager = new CompressionMethodManager([new Deflate(),]);
        $this->serializer = new CompactSerializer(); // The serializer
    }

    public function getSecret(string $technology){
        switch($technology){
            case('webwork'):
                return file_get_contents(base_path() . '/JWE/webwork');
                break;
            default:
                throw new Exception("$technology has no secret associated with it.");
        }
    }


    public function encrypt(string $jwt, string $technology)
    {

        $jwk = JWKFactory::createFromSecret(
            $this->getSecret($technology)
        );

        $jweBuilder = new JWEBuilder(
            $this->keyEncryptionAlgorithmManager,
            $this->contentEncryptionAlgorithmManager,
            $this->compressionMethodManager
        );

        $payload = json_encode($this->getPayload($jwt),JSON_UNESCAPED_SLASHES);

        $jwe = $jweBuilder
            ->create()              // We want to create a new JWE
            ->withPayload($payload) // We set the payload
            ->withSharedProtectedHeader(['alg' => 'PBES2-HS512+A256KW',        // Key Encryption Algorithm
                'enc' => 'A256GCM', // Content Encryption Algorithm
                'zip' => 'DEF'])            // We enable the compression (irrelevant as the payload is small, just for the example).])
            ->addRecipient($jwk)    // We add a recipient (a shared key or public key).
            ->build();              // We build it

        return  $this->serializer->serialize($jwe, 0); // We serialize the recipient at index 0 (we only have one recipient).
    }

    public function decrypt(string $token, string $technology)
    {

        $jweDecrypter = new JWEDecrypter(
            $this->keyEncryptionAlgorithmManager,
            $this->contentEncryptionAlgorithmManager,
            $this->compressionMethodManager
        );
        $jwk = JWKFactory::createFromSecret(
            $this->getSecret($technology)
        );
        $jwe = $this->serializer->unserialize($token);
        $success = $jweDecrypter->decryptUsingKey($jwe, $jwk, 0);
        return $success ? $jwe->getPayload() : false;

    }

}
