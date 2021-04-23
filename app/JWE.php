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
                if (app()->environment('testing')){
                    return '7q3Fu7C9PKUhxTQO0kjRU7JazTL4MSkp9aLSGFAIlB7ucxu+uhDDRigk2P6vkKmazYnqO423uH/r9knya1iSGRcXjg5jmHYhdkOZEe+yseLRX2t2rAwKgrCYqxgOw9c5oxnfnpDNJ4wbC2twnyqcYhgPJgNIiPsDjTWRg6Eux4dP3Wlzl9/owq3zp09Jp7Fq3DDrr8HHauRTlSLiKOlOwp6+D/BkWDjqCsLnhfHVtv0CntF7YrhGQoi8j7/n8PDolytOvDd9EAstKYQ/yUIloD8FKPWQDJlmb0DiFl3tm7bfc0HslcxfhTTmJYI6OFwvDIPf/3lW4TD/89yb9Fw9XmIPhDKlMUiVIqQtfl3AOdEpt/ORk3ndrW+h0Hav8egnwE4aYYG0BKC/g9R/QSssMJ1Jk+iWubZMWtOKX4T5aPL+qxnBLRR6CpJBJi287HSHWx86lryjOADz57cwu/F+BjC/TY5P1kq09t3g7v4PtAKuOTN8ZkfXIi/NWWGtEMwEJF97mCqN/e2f9ff/yBI3sXOt17aHpJouvA4EMOaPFklJu+lOsnzqzmWa7IbDveYwNd1wQyz042/EoIkc5zh+7SZzNH+O2/Qd5aMxScLtjooxReT+BT6YPvymRKplQwRnSfDn1zamymbMiYvCK4PQ9Np5SvmKEy7hZG9bpBqQMhhY7sfRnLo1ICuin+2V6Sc49jYs+g+L+Y3cWNjQB7nNY5A1orjuAGXKKSKW4CnBkZ8g787bsLn7K6we/OuuOwQTcEckSrSma76z4YE7BhHdfZwi+hcblI/kaUbrUMVkqNr4xcHjna0ImzfRg/1HB3Q1V3KnuXPwkyx2TxHp1UqpsYE2b5HEvHx5Wmgp8UNwcGGsh9FgeHsohT2L7/+aS2+dPPMreDPgIAIs3rjstitxlrOOtcExKCbPRd5+7zZGdsH5IaX5josqdp/LltyRta6fkgWLDdmBp4B1pp7gjqGUU3YZ+nSeBasaHpxuuF2Wlr8n5GMrOEkHbPMbYmNKB/Pl';
                }
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

        $payload = json_encode($this->getPayload($jwt, $this->getSecret($technology)),JSON_UNESCAPED_SLASHES);

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
