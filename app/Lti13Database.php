<?php

namespace App;

use Exception;
use Illuminate\Support\Facades\DB;
use Packback\Lti1p3\Interfaces\IDatabase;
use Packback\Lti1p3\LtiRegistration;
use Packback\Lti1p3\LtiDeployment;
use Packback\Lti1p3\OidcException;


class Lti13Database implements IDatabase
{
    public static function findIssuer($issuer_url, $client_id = null)
    {
        $query = Issuer::where('iss', $issuer_url);
        if ($client_id) {
            $query = $query->where('client_id', $client_id);
        }
        if ($query->count() > 1) {
            throw new OidcException('Found multiple registrations for the given issuer, ensure a client_id is specified on login (contact your LMS administrator)', 1);
        }
        return $query->first();
    }

    /**
     * @throws OidcException
     * @throws Exception
     */
    public function findRegistrationByIssuer($issuer, $client_id = null)
    {
        $issuer = self::findIssuer($issuer, $client_id);
        if (!$issuer) {
            return false;
        }


        switch (app()->environment()){
            case('local'):
                $base_dir =  '/Users/franciscaparedes/adapt_laravel_8/storage/app';
                break;
            case('dev'):
                $base_dir = '/var/www';
                break;
            case('production'):
            case('staging'):
                $base_dir = '/mnt/local/';
                break;
            default:
                $base_dir = '';
        }
        if (!$base_dir){
            throw new Exception('No base directory for private key.');
        }
        if (!is_dir("$base_dir/lti")){
            throw new Exception("$base_dir/lti is not a valid directory.");
        }
        if (!file_exists("$base_dir/lti/private.key")) {
            throw new Exception("Private key file does not exist.");
        }
        $private_key = file_get_contents("$base_dir/lti/private.key");

        return LtiRegistration::new()
            ->setAuthTokenUrl($issuer->auth_token_url)
            ->setAuthLoginUrl($issuer->auth_login_url)
            ->setClientId($issuer->client_id)
            ->setKeySetUrl($issuer->key_set_url)
            ->setKid($issuer->kid)
            ->setIssuer($issuer->issuer)
            ->setToolPrivateKey($private_key);
    }

    public function findDeployment($issuer, $deployment_id, $client_id = null)
    {

        $issuerModel = self::findIssuer($issuer, $client_id);
        if (!$issuerModel) {
            return false;
        }

        $deployment = DB::table('lti_deployments')->where('id', $deployment_id)->first();
        if (!$deployment) {
            return false;
        }

        return LtiDeployment::new()
            ->setDeploymentId($deployment->id);

    }
}
