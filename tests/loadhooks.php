<?php

namespace AbraFlexi\Acceptor;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2021 Spoje.Net
 */
define('APP_NAME', 'WebHookAcceptoTest');
define('EASE_LOGGER', 'console|syslog');
require_once __DIR__ . '/../vendor/autoload.php';

\Ease\Shared::singleton()->loadConfig('../.env', true);

$hooker = new HookReciever();
$hooker->logBanner();

//$hooker->debug = true;

foreach (scandir(__DIR__ . '/hooks') as $hookfile) {
    if ($hookfile[0] != '.') {
        $apiResponseRaw = $hooker->listen(__DIR__ . '/hooks/' . $hookfile);
        if (!empty($apiResponseRaw) && $hooker->takeChanges($apiResponseRaw)) {
            $hooker->addStatusMessage($hookfile, $hooker->saveWebhookData($hooker->onlyFreshHooks($hooker->changes)) ? 'success' : 'warning' );
        }
    }
}
