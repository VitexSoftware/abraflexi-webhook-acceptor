<?php

namespace AbraFlexi\Acceptor;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2020 Spoje.Net
 */
define('APP_NAME', 'WebHookInstaller');
define('EASE_LOGGER', 'syslog');
require_once __DIR__ . '/../vendor/autoload.php';

$cfg = '../.env';
if (file_exists($cfg)) {
    \Ease\Shared::singleton()->loadConfig($cfg, true);
}

$hooker = new Hooker(null, ['debug' => boolval(\Ease\Functions::cfg('APP_DEBUG'))]);
$hooker->logBanner();
if ($hooker->debug) {
    $hooker->setDataValue('skipUrlTest', 'true');
}

$endpoint = Hooker::webHookUrl(strval(time()));

try {
    $hooker->register($endpoint);
    $hooker->addStatusMessage(sprintf(_('Registering %s in AbraFlexi'), $endpoint), 'success');
} catch (\AbraFlexi\Exception $ex) {
    $hooker->addStatusMessage(sprintf(_('Registering %s in AbraFlexi') . ' ' . _('Failed'), $endpoint), 'error');
}

try {
    $reciever = new HookReciever(['throwException' => false]);
    $reciever->addStatusMessage(_('Last Processed version set to 0'), $reciever->saveLastProcessedVersion(0) ? 'success' : 'warning' );
} catch (Exception $exc) {
    echo $exc->getTraceAsString();
}

