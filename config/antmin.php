<?php

return [


    'name'       => 'Ant Admin',
    'logStorage' => 'redis',

    // Token 和日志配置统一由包提供，宿主项目可通过发布配置覆盖。
    'token'      => [
        'ttl'              => (int) env('ANTMIN_TOKEN_TTL', 43200),
        'max_devices'     => (int) env('ANTMIN_TOKEN_MAX_DEVICES', 3),
        'redis_prefix'    => env('ANTMIN_TOKEN_REDIS_PREFIX', 'antmin:account_tokens:'),
    ],
    'log_sql'    => (bool) env('ANTMIN_LOG_SQL', false),
    'upload'     => [
        'url' => env('ANTMIN_UPLOAD_URL', rtrim((string) env('APP_URL', ''), '/') . '/storage'),
    ],
    'database'   => [
        'connection'   => env('ANTMIN_DATABASE_CONNECTION', null),
        'account_table' => env('ANTMIN_ACCOUNT_TABLE', 'system_account'),
        'log_connection' => env('ANTMIN_LOG_DATABASE_CONNECTION', null),
        'schema_path' => env('ANTMIN_SCHEMA_PATH', null),
    ],
    'default_password' => env('ANTMIN_DEFAULT_PASSWORD', env('ADMIN_PASSWORD', '86662825')),

];
