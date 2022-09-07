<?php

namespace AbraFlexi\Acceptor;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2020 Spoje.Net, 2021-2022 VitexSoftware
 */
define('APP_NAME', 'WebHookAcceptor');
define('EASE_LOGGER', 'syslog');
require_once __DIR__ . '/../vendor/autoload.php';

$cfg = '../.env';
if (file_exists($cfg)) {
    \Ease\Shared::singleton()->loadConfig($cfg, true);
}
if (\Ease\WebPage::isPosted()) {
    try {
        if (isset($_SERVER['REMOTE_HOST'])) {
            if (array_key_exists('company', $_REQUEST)) {
                define('ABRAFLEXI_COMPANY', $_REQUEST['company']);
            }   
        }
        
        $hooker = new HookReciever();
        $hooker->debug = true;
        $apiResponseRaw = $hooker->listen();
        if (!empty($apiResponseRaw) && $hooker->takeChanges($apiResponseRaw)) {
            $hooker->saveWebhookData($hooker->onlyFreshHooks($hooker->changes));
        } else {
            echo 'No changes';
        }
    } catch (\AbraFlexi\Exception $exc) {
        echo $exc->getMessage();
        exit(500);
    }
} else {
    $oPage = new \Ease\TWB4\WebPage(\Ease\Shared::appName());
    $oPage->redirect('installer.php');
    $oPage->addItem(new \Ease\Html\ATag('installer.php', _('Run the installer')));
    echo $oPage;
}
