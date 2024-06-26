<?php
return [
    'is_me_cookie' => env('IS_ME_COOKIE', 'no is me cookie'),
    'temp_is_me_cookie' => env('TEMP_IS_ME_COOKIE', 'no temp is me cookie'),
    'telegram_channel_id' => env('TELEGRAM_CHANNEL_ID', 'no telegram channel id'),
    'webwork_seed' => env('WEBWORK_SEED', 'no webwork seed'),
    'webwork_token' => env('WEBWORK_TOKEN', false),
    'imathas_seed' => env('IMATHAS_SEED', 'no imathas seed'),
    'jwt_secret' => env('JWT_SECRET', null),
    'webwork_jwt_secret' => env('WEBWORK_JWT_SECRET', file_exists(base_path() . '/JWE/webwork')
        ? file_get_contents(base_path() . '/JWE/webwork')
        : 'No string exists'),
    'db_host' => env('DB_HOST', 'No host provided'),
    'minpods' => env('MINPODS', 0),
    'loadtest' => env('LOADTEST', false),
    'my_ip' => env('MY_IP', false),
    'analytics_token' => env('ANALYTICS_TOKEN', false),
    'analytics_dashboard_token' => env('ANALYTICS_DASHBOARD_TOKEN', false),
    'libretexts_private_page_token' => env('LIBRETEXTS_PRIVATE_PAGE_TOKEN', false),
    's3_bucket' => env('APP_VAPOR') ? env('AWS_VAPOR_BUCKET') : env('AWS_BUCKET'),
    'h5p_api_username' => env('H5P_API_USERNAME', ''),
    'h5p_api_password' => env('H5P_API_PASSWORD', ''),
];
