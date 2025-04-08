<?php

declare(strict_types=1);

/**
 * This file is part of the MultiFlexi package
 *
 * https://github.com/VitexSoftware/abraflexi-webhook-acceptor
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AbraFlexi\Acceptor;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@vitexsoftware.com>
 * @copyright  2017-2020 Spoje.Net, 2021-2024 VitexSoftware
 */
\define('APP_NAME', 'WebHookAcceptor');
\define('EASE_LOGGER', 'syslog');

require_once __DIR__.'/../vendor/autoload.php';

\Ease\Shared::init(['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'], '../.env');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    try {
        $config = [];

        if (\array_key_exists('company', $_REQUEST)) {
            \define('ABRAFLEXI_COMPANY', $_REQUEST['company']);
            $config['company'] = $_REQUEST['company'];
        }

        $hooker = new HookReciever($config);
        $hooker->debug = true;
        $apiResponseRaw = $hooker->listen();

        if (!empty($apiResponseRaw) && $hooker->takeChanges($apiResponseRaw)) {
            $hooker->saveWebhookData($hooker->onlyFreshHooks($hooker->changes));
        } else {
            $hooker->addStatusMessage(_('Webhook with empty body'), 'warning');
        }
    } catch (\AbraFlexi\Exception $exc) {
        echo $exc->getMessage();

        exit(500);
    }
} else {
    $oPage = new \Ease\TWB5\WebPage(\Ease\Shared::appName());
    $oPage->redirect('installer.php');
    $oPage->addItem(new \Ease\Html\ATag('installer.php', _('Run the installer')));
    echo $oPage;
}
