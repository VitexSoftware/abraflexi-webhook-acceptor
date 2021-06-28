<?php

namespace AbraFlexi\Acceptor;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2020 Spoje.Net
 */
define('APP_NAME', 'WebHookAcceptor');
define('EASE_LOGGER', 'syslog');
require_once __DIR__ . '/../vendor/autoload.php';

$cfg = '../.env';
if (file_exists($cfg)) {
    \Ease\Shared::singleton()->loadConfig($cfg, true);
}

try {
    $hooker = new HookReciever();
} catch (\AbraFlexi\Exception $exc) {
    echo $exc->getMessage();
    echo new \Ease\Html\ATag('installer.php', _('Run the installer'));
    (new \Ease\WebPage)->redirect('installer.php');
    exit;
}

//$hooker->debug = true;

$apiResponseRaw = $hooker->listen();
if (!empty($apiResponseRaw) && $hooker->takeApiChanges($apiResponseRaw)) {
    $hooker->saveWebhookData($hooker->onlyFreshHooks($hooker->changes));
}
