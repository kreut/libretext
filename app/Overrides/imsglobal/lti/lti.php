<?php
// Import all
foreach (glob(__DIR__ . "/*.php") as $filename) {
    require_once $filename;
}
define("TOOL_HOST", ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?: $_SERVER['REQUEST_SCHEME']) . '://' . $_SERVER['HTTP_HOST']);
App\Overrides\imsglobal\lti\Firebase\JWT::$leeway = 5;
?>
