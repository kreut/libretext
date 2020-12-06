<?php

namespace App\Custom;

use App\LtiRegistration;
use App\LtiKey;
use App\LtiDeployment;
use \IMSGlobal\LTI;

class LTIDatabase implements LTI\Database
{

    public function find_registration_by_issuer($iss)
    {
        $lti_registration = LtiRegistration::where('iss', $iss)->first();

        return LTI\LTI_Registration::new()
            ->set_auth_login_url($lti_registration->auth_login_url)
            ->set_auth_token_url($lti_registration->auth_token_url)
            ->set_auth_server($lti_registration->auth_server)
            ->set_client_id($lti_registration->client_id)
            ->set_key_set_url($lti_registration->key_set_url)
            ->set_kid($lti_registration->kid)
            ->set_issuer($iss)
            ->set_tool_private_key($this->private_key($lti_registration->lti_key_id));

    }

    public function find_deployment($iss, $deployment_id)
    {
        $registration_id = LtiRegistration::where('iss', $iss)->first()->id;
        if (LtiDeployment::where('id', $deployment_id)->where('registration_id', $registration_id)->get()->isEmpty()) {
            $lti_deployment = new LtiDeployment();
            $lti_deployment->id = $deployment_id;
            $lti_deployment->registration_id = $registration_id;
            $lti_deployment->save();
        }
        return LTI\LTI_Deployment::new()
            ->set_deployment_id($deployment_id);
    }

    private function private_key($lti_key_id)
    {
//$private_key_file = LtiKey::where('id',$lti_key_id)->first()->private_key_file;

        return file_get_contents('/var/www/lti/private.key');
    }
}

