<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db/example_database.php';

use \IMSGlobal\LTI;

$launch = LTI\LTI_Message_Launch::from_cache($launch_id, new Example_Database());
if (!$launch->is_deep_link_launch()) {
    throw new Exception("Must be a deep link!");
}

$resource = LTI\LTI_Deep_Link_Resource::new()
    ->set_url(TOOL_HOST . "/api/lti/game")
    ->set_custom_params(['difficulty' => 'easy'])
    ->set_title('Breakout mode!');
$launch->get_deep_link()
    ->output_response_form([$resource]);
?>
