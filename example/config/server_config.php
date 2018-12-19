<?php
/**
 * Description
 * User: lv_fan2008@sina.com
 * Time: 2018/12/19 13:36
 */
return [
    'app_id' => env("OPEN_APP_ID", ''),
    'secret' => env("OPEN_SECRET", ''),
    'token' => env("OPEN_TOKEN", ''),
    'aes_key' => env("OPEN_AES_KEY", ''),
    'base_uri' => "http:/" . env("SERVER_DOMAIN", "") . "/server",
    'cache' => [
        'path' => dirname(__FILE__) . "/../cache/server",
    ],
    'log' => [
        'default' => 'single',
        'channels' => [
            'single' => [
                'driver' => 'single',
                'path' => dirname(__FILE__) . "/../log/server.log",
                'level' => 'debug',
            ],
        ],
    ],
];