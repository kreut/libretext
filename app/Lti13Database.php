<?php

namespace App;

use Packback\Lti1p3\Interfaces\IDatabase;
use Packback\Lti1p3\LtiRegistration;
use Packback\Lti1p3\LtiDeployment;
use Packback\Lti1p3\OidcException;


class Lti13Database implements IDatabase
{
    public static function findIssuer($issuer_url, $client_id = null)
    {
        $query = Issuer::where('issuer', $issuer_url);
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
     */
    public function findRegistrationByIssuer($issuer, $client_id = null)
    {
        $issuer = self::findIssuer($issuer, $client_id);
        if (!$issuer) {
            return false;
        }

        return LtiRegistration::new()
            ->setAuthTokenUrl($issuer->auth_token_url)
            ->setAuthLoginUrl($issuer->auth_login_url)
            ->setClientId($issuer->client_id)
            ->setKeySetUrl($issuer->key_set_url)
            ->setKid($issuer->kid)
            ->setIssuer($issuer->issuer)
            ->setToolPrivateKey($issuer->tool_private_key);
    }

    public function findDeployment($issuer, $deployment_id, $client_id = null)
    {
        $issuerModel = self::findIssuer($issuer, $client_id);
        if (!$issuerModel) {
            return false;
        }
        $deployment = $issuerModel->deployments()->where('deployment_id', $deployment_id)->first();
        if (!$deployment) {
            return false;
        }

        return LtiDeployment::new()
            ->setDeploymentId($deployment->id);
    }
}
