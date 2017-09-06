<?php
/**
 * repo.php
 *
 * Creator:         chongyi
 * Create Datetime: 2017/5/7 13:45
 */

return [
    'proxy' => [
        'url' => env('COMPOSER_PROXY_URL', 'https://packagist.phpcomposer.com'),
    ],

    'dist_storage' => env('COMPOSER_DIST_STORAGE', public_path('download')),
    'dist_url' => env('COMPOSER_DIST_URL', 'http://packagist.yunsom.cn'),

    'local' => [
        'web-hook' => [
            'gitlab' => [
                'username' => env('GITLAB_HTTP_AUTH_USERNAME'),
                'password' => env('GITLAB_HTTP_AUTH_PASSWORD'),
            ]
        ]
    ]
];