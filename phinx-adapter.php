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

$cfg = '../.env';

\Ease\Shared::init(['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'], $cfg , true);
$dbtype = \Ease\Shared::cfg('DB_CONNECTION');

$prefix = file_exists('./db/') ? './db/' : '../db/';

$sqlOptions = [];

if (strstr($dbtype, 'sqlite')) {
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
            'adapter' => $dbtype,
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions)
            ],
        'production' => [
            'adapter' => $dbtype,
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions)
            ]
        ]
];

