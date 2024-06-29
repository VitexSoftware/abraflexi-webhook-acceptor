<?php

namespace AbraFlexi\Acceptor;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2024 Spoje.Net
 */
define('APP_NAME', 'WebHookAcceptoTest');
define('EASE_LOGGER', 'console|syslog');
require_once __DIR__ . '/../vendor/autoload.php';

\Ease\Shared::init(['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'], '../.env', true);

$hooker = new HookReciever(['throwException' => false, 'debug' => true]);
$hooker->logBanner();
$hooker->saveLastProcessedVersion(1);

//$hooker->debug = true;

$_SERVER['REMOTE_HOST'] = 'localhost'; //TODO

foreach (scandir(__DIR__ . '/hooks') as $hookfile) {
    if ($hookfile[0] != '.') {
        $apiResponseRaw = $hooker->listen(__DIR__ . '/hooks/' . $hookfile);
        if (!empty($apiResponseRaw) && $hooker->takeChanges($apiResponseRaw)) {
            $hooker->addStatusMessage($hookfile, $hooker->saveWebhookData($hooker->onlyFreshHooks($hooker->changes)) ? 'success' : 'warning' );
        }
    }
}
