<?php

return [
    /*
    |--------------------------------------------------------------------------
    | BasePack Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for BasePack DevOps toolkit
    |
    */

    'project_name' => env('BASEPACK_PROJECT_NAME', env('APP_NAME', 'laravel')),
    
    'docker' => [
        'php_version' => env('BASEPACK_PHP_VERSION', '8.3'),
        'node_version' => env('BASEPACK_NODE_VERSION', '20'),
        'mysql_version' => env('BASEPACK_MYSQL_VERSION', '8.0'),
        'redis_version' => env('BASEPACK_REDIS_VERSION', 'alpine'),
        'nginx_version' => env('BASEPACK_NGINX_VERSION', 'alpine'),
    ],
    
    'ports' => [
        'web_http' => env('WEB_PORT_HTTP', 80),
        'web_https' => env('WEB_PORT_HTTPS', 443),
        'mysql' => env('DB_OUTER_PORT', 3306),
        'redis' => env('REDIS_OUTER_PORT', 6379),
    ],
    
    'xdebug' => [
        'enabled' => env('BASEPACK_XDEBUG_ENABLED', true),
        'config' => env('XDEBUG_CONFIG', 'main'),
        'port' => env('XDEBUG_PORT', 10000),
    ],
    
    'ssl' => [
        'generate' => env('BASEPACK_SSL_GENERATE', true),
        'domain' => env('BASEPACK_SSL_DOMAIN', 'localhost'),
    ],
];