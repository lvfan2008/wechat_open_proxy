<?php
return [
    'proxy_base_uri' => 'http://' . env("SERVER_DOMAIN", '') . '/server/proxy/api/',
    'proxy_auth_url' => 'http://' . env("SERVER_DOMAIN", '') . '/server/proxy/auth/show',
    'cache' => [
        'path' => dirname(__FILE__) . "/../cache/client",
    ],
    'log' => [
        'default' => 'single',
        'channels' => [
            'single' => [
                'driver' => 'single',
                'path' => dirname(__FILE__) . "/../log/client.log",
                'level' => 'debug',
            ],
        ],
    ],
];