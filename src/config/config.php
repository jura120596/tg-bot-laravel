<?php
return [
    'user_column' => env('TG_BOT_USER_COLUMN_NAME', 'tg_id'),
    'key' =>        env('TG_BOT_KEY', 'tg_bot_key'),
    'botname' =>    env('TG_BOT_NAME', 'tg_name_bot'),
    'whurl' =>      env('TG_BOT_WHURL', env('APP_URL', 'https://demo.ru')),
    'paytoken' => env('TG_PAY_TOKEN', 'token'),
    'start' => env('TG_BOT_PAY_START', 'test'),
    'dev_id' => env('TG_DEV_USER_ID'),
    'route_path' => '/api/botcmd',
];
