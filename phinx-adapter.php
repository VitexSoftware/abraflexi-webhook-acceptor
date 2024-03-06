<?php

/**
 * Abra Flexi Webhook Acceptor - Phinx database adapter.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2021-2024 Vitex Software
 */
if (file_exists('./vendor/autoload.php')) {
    include_once './vendor/autoload.php';
} else {
    include_once '../vendor/autoload.php';
}


\Ease\Shared::init(['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'], '../.env', true);

$prefix = file_exists('./db/') ? './db/' : '../db/';

$sqlOptions = [];

if (strstr(\Ease\Shared::cfg('DB_CONNECTION'), 'sqlite')) {
    $sqlOptions['database'] = \Ease\Shared::cfg('DB_DATABASE');
    if (!file_exists($sqlOptions['database'])) {
        file_put_contents($sqlOptions['database'], '');
    }
}

$engine = new \Ease\SQL\Engine(null, $sqlOptions);
return [
    'paths' => [
        'migrations' => [$prefix . 'migrations'],
        'seeds' => [$prefix . 'seeds/']
    ],
    'environments' =>
    [
        'default_environment' => 'development',
        'development' => [
            'adapter' => \Ease\Shared::cfg('DB_TYPE'),
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions)
        ],
        'production' => [
            'adapter' => \Ease\Shared::cfg('DB_TYPE'),
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions)
        ]
    ]
];
