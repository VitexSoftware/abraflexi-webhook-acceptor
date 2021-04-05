<?php

namespace AbraFlexi\Acceptor;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2020 Spoje.Net
 */
define('APP_NAME', 'WebHookAcceptor');
require_once __DIR__ . '/../vendor/autoload.php';

\Ease\Shared::singleton()->loadConfig('../.env',true);


$hooker = new \AbraFlexi\Bricks\HookReciever();
//$hooker->debug = true;

$apiResponseRaw = $hooker->listen();
if (!empty($apiResponseRaw) && $hooker->takeApiChanges($apiResponseRaw)) {
    $hooker->saveWebhookData($hooker->onlyFreshHooks($hooker->changes));
}
