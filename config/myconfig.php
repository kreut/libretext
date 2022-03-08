<?php
return [
    'is_me_cookie' => env('IS_ME_COOKIE', 'no is me cookie'),
    'telegram_channel_id' => env('TELEGRAM_CHANNEL_ID', 'no telegram channel id'),
    'webwork_seed' => env('WEBWORK_SEED', 'no webwork seed'),
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
    'libretexts_private_page_token' => env('LIBRETEXTS_PRIVATE_PAGE_TOKEN', false),
    's3_bucket' => env('APP_VAPOR') ? env('AWS_VAPOR_BUCKET') : env('AWS_BUCKET')
];
