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
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2024 Spoje.Net
 */
\define('APP_NAME', 'WebHookAcceptoTest');
\define('EASE_LOGGER', 'console|syslog');

require_once __DIR__.'/../vendor/autoload.php';

\Ease\Shared::init(['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'], '../.env', true);

$oPage = new \Ease\WebPage();

$hookList = new \Ease\Html\UlTag();

$hook = \Ease\TWB5\WebPage::getGetValue('hook');

$hookForm = new \Ease\Html\Form(['method' => 'POST', 'action' => '../src/webhook.php?company=test', 'enctype' => 'text/plain']);
$hookForm->addItem(new \Ease\Html\TextareaTag('', !empty($hook) && file_exists(__DIR__.'/hooks/'.$hook) ? file_get_contents(__DIR__.'/hooks/'.$hook) : '', ['cols' => 80, 'rows' => 20]));
$hookForm->addItem(new \Ease\TWB5\SubmitButton('📦 Hook'));
$oPage->addItem($hookForm);

foreach (scandir(__DIR__.'/hooks') as $hookfile) {
    $hookList->addItemSmart(new \Ease\Html\ATag('?hook='.$hookfile, $hookfile));
}

$oPage->addItem($hookList);

echo $oPage;
