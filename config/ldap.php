<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default LDAP Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the LDAP connections below you wish
    | to use as your default connection for all LDAP operations. Of
    | course you may add as many connections you'd like below.
    |
    */

    'default' => env('LDAP_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | LDAP Connections
    |--------------------------------------------------------------------------
    |
    | Below you may configure each LDAP connection your application requires
    | access to. Be sure to include a valid base DN - otherwise you may
    | not receive any results when performing LDAP search operations.
    |
    */

    'connections' => [

        'default' => [
            'hosts' => explode(',', env('LDAP_HOST', '10.111.121.10,10.111.121.20')),
            'username' => env('LDAP_USERNAME', 'svc-ad-portal@cmc.mhe.local'),
            'password' => env('LDAP_PASSWORD', ''),
            'port' => (int) env('LDAP_PORT', 389),
            'base_dn' => env('LDAP_BASE_DN', 'OU=CMC.MHE,DC=cmc,DC=mhe,DC=local'),
            'timeout' => (int) env('LDAP_TIMEOUT', 5),
            'use_tls' => env('LDAP_TLS', false),
            'use_starttls' => env('LDAP_STARTTLS', true),
            'use_sasl' => env('LDAP_SASL', false),
            'allow_insecure_password_changes' => env('LDAP_ALLOW_INSECURE_PASSWORD_CHANGES', false),
            'options' => [
                LDAP_OPT_X_TLS_REQUIRE_CERT => 0,
            ],
        ],

        'bds' => [
            'hosts' => explode(',', env('LDAP_BDS_HOST', '10.111.121.10,10.111.121.20')),
            'username' => env('LDAP_BDS_USERNAME', 'svc-ad-portal@cmc.mhe.local'),
            'password' => env('LDAP_BDS_PASSWORD', ''),
            'port' => (int) env('LDAP_BDS_PORT', 389),
            'base_dn' => env('LDAP_BDS_BASE_DN', 'DC=bds,DC=cmc,DC=mhe,DC=local'),
            'timeout' => (int) env('LDAP_TIMEOUT', 5),
            'use_tls' => env('LDAP_TLS', false),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Logging
    |--------------------------------------------------------------------------
    |
    | When LDAP logging is enabled, all LDAP search and authentication
    | operations are logged using the default application logging
    | driver. This can assist in debugging issues and more.
    |
    */

    'logging' => [
        'enabled' => env('LDAP_LOGGING', true),
        'channel' => env('LOG_CHANNEL', 'stack'),
        'level' => env('LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Cache
    |--------------------------------------------------------------------------
    |
    | LDAP caching enables the ability of caching search results using the
    | query builder. This is great for running expensive operations that
    | may take many seconds to complete, such as a pagination request.
    |
    */

    'cache' => [
        'enabled' => env('LDAP_CACHE', false),
        'driver' => env('CACHE_DRIVER', 'file'),
    ],

];
