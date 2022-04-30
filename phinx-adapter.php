<?php

/**
 * Abra Flexi Webhook Acceptor - Phinx database adapter.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2021-2022 Vitex Software
 */
if (file_exists('./vendor/autoload.php')) {
    include_once './vendor/autoload.php';
} else {
    include_once '../vendor/autoload.php';
}


$cfg = __DIR__ . '/.env';

if(file_exists($cfg)){
    \Ease\Shared::singleton()->loadConfig($cfg, true);
} else {
  echo "Warning: $cfg not exist "
}



$prefix = file_exists('./db/') ? './db/' : '../db/';

$sqlOptions = [];

if (strstr(\Ease\Functions::cfg('DB_CONNECTION'), 'sqlite')) {
    $sqlOptions['database'] = __DIR__ . '/' . basename(\Ease\Functions::cfg('DB_DATABASE'));
    if (!file_exists($sqlOptions['database'])) {
        file_put_contents($sqlOptions['database'], '');
    }
}
$engine = new \Ease\SQL\Engine(null, $sqlOptions);
$cfg = [
    'paths' => [
        'migrations' => [$prefix . 'migrations'],
        'seeds' => [$prefix . 'seeds/']
    ],
    'environments' =>
    [
        'default_database' => 'development',
        'development' => [
            'adapter' => \Ease\Functions::cfg('DB_TYPE'),
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions)
        ],
        'default_database' => 'production',
        'production' => [
            'adapter' => \Ease\Functions::cfg('DB_TYPE'),
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions)
        ],
    ]
];

return $cfg;
