<?php

namespace AbraFlexi\Acceptor;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2020 Spoje.Net
 */
define('APP_NAME', 'WebHookInstaller');
require_once __DIR__ . '/../vendor/autoload.php';

\Ease\Shared::singleton()->loadConfig('../.env',true);



$hooker = new Hooker();

$hooker->register(Hooker::webHookUrl(strval(time()) ) );

