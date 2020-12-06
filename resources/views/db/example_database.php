<?php
require_once __DIR__ . '/../vendor/autoload.php';
//define("TOOL_HOST", ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?: $_SERVER['REQUEST_SCHEME']) . '://' . $_SERVER['HTTP_HOST']);
define("TOOL_HOST", 'https://dev.adapt.libretexts.org');
session_start();
use \IMSGlobal\LTI;

$_SESSION['iss'] = [];
$reg_configs = array_diff(scandir(__DIR__ . '/configs'), array('..', '.', '.DS_Store'));
foreach ($reg_configs as $key => $reg_config) {
    $_SESSION['iss'] = array_merge($_SESSION['iss'], json_decode(file_get_contents(__DIR__ . "/configs/$reg_config"), true));
}

class Example_Database implements LTI\Database {
    public function find_registration_by_issuer($iss) {
        $iss = 'https://dev-canvas.libretexts.org';
        $_SESSION['iss'][$iss]['auth_server'] = 'https://dev-canvas.libretexts.org';

        $_SESSION['iss'][$iss]['kid'] = '58f36e10-c1c1-4df0-af8b-85c857d1634f';
        $_SESSION['iss'][$iss]['client_id'] = '10000000000017';
        $_SESSION['iss'][$iss]['auth_login_url'] = "https://dev-canvas.libretexts.org/api/lti/authorize_redirect";
        $_SESSION['iss'][$iss]['auth_token_url'] =  "https://dev-canvas.libretexts.org/login/oauth2/token";
        $_SESSION['iss'][$iss]['key_set_url'] = "https://dev-canvas.libretexts.org/api/lti/security/jwks";
        $_SESSION['iss'][$iss]['private_key_file'] = "/private.key";
        $_SESSION['iss'][$iss]['deployment'] = ['22:8865aa05b4b79b64a91a86042e43af5ea8ae79eb'];//WHERE to find???????
        if (empty($_SESSION['iss']) || empty($_SESSION['iss'][$iss])) {
            return false;
        }

        return LTI\LTI_Registration::new()
            ->set_auth_login_url($_SESSION['iss'][$iss]['auth_login_url'])
            ->set_auth_token_url($_SESSION['iss'][$iss]['auth_token_url'])
            ->set_auth_server($_SESSION['iss'][$iss]['auth_server'])
            ->set_client_id($_SESSION['iss'][$iss]['client_id'])
            ->set_key_set_url($_SESSION['iss'][$iss]['key_set_url'])
            ->set_kid($_SESSION['iss'][$iss]['kid'])
            ->set_issuer($iss)
            ->set_tool_private_key($this->private_key($iss));
    }

    public function find_deployment($iss, $deployment_id) {
        if (!in_array($deployment_id, $_SESSION['iss'][$iss]['deployment'])) {
            return false;
        }
        return LTI\LTI_Deployment::new()
            ->set_deployment_id($deployment_id);
    }

    private function private_key($iss) {
        return file_get_contents(__DIR__ . $_SESSION['iss'][$iss]['private_key_file']);
    }
}
?>
