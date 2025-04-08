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
\define('APP_NAME', 'WebHookInstaller');
\define('EASE_LOGGER', 'syslog');

require_once __DIR__.'/../vendor/autoload.php';

\Ease\Shared::init(['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'], '../.env');

$success = false;
$hookurl = str_replace(basename(__FILE__), 'webhook.php', \Ease\Document::phpSelf());
$oPage = new \Ease\TWB5\WebPage(_('WebHook acceptor installer'));

 $baseUrl = \dirname(\Ease\WebPage::phpSelf());

 $loginForm = new \AbraFlexi\ui\TWB5\ConnectionForm(['action' => 'install.php']);
 
// $loginForm->addInput( new \Ease\Html\InputUrlTag('myurl'), _('My Url'), dirname(\Ease\Page::phpSelf()), sprintf( _('Same url as you can see in browser without %s'), basename( __FILE__ ) ) );

 $loginForm->fillUp(\Ease\WebPage::isPosted() ? $_REQUEST : \Ease\Shared::instanced()->configuration);

 $loginForm->addItem(new \Ease\TWB5\SubmitButton(_('Install WebHook'), 'success btn-lg btn-block'));

 if ($oPage->isPosted()) {
    try {
        $format = 'json';
        $hooker = new \AbraFlexi\Hooks(null, $_REQUEST);
        $hookResult = $hooker->register(\Ease\Functions::addUrlParams($hookurl, ['company' => $hooker->getCompany()]));

        if ($hookResult) {
            $hooker->addStatusMessage(sprintf(_('Hook %s was registered'), $hookurl), 'success');
            $hookurl = '';
        } else {
            $hooker->addStatusMessage(sprintf(_('Hook %s not registered'), $hookurl), 'warning');
        }
    } catch (\Exception $exc) {
        $oPage->addStatusMessage($exc->getMessage(), 'warning');
    }
 } else {
    $oPage->addStatusMessage(_('WebHook Acceptor URL').': '.$baseUrl);
 }

 if (\array_key_exists('REMOTE_HOST', $_SERVER) === false) {
    $_SERVER['REMOTE_HOST'] = $_SERVER['REMOTE_ADDR'];

    switch ($_SERVER['SERVER_SOFTWARE']) {
        case 'Apache':
            $oPage->addStatusMessage(_('Add HostnameLookups On to your Apache configuration'), 'warning');

            break;
        case 'nginx':
            $_SERVER['REMOTE_HOST'] = gethostbyaddr($_SERVER['REMOTE_ADDR']);

            break;

        default:
            $oPage->addStatusMessage(_('REMOTE_HOST is not set. Is HostnameLookups On ?'), 'warning');
    }
 }

 $setupRow = new \Ease\TWB5\Row();

 if ($success) {
    $setupRow->addColumn(6, new \Ease\Html\H2Tag(_('Done')));
 } else {
    $setupRow->addColumn(6, $loginForm);
 }

 $setupRow->addColumn(6, [new Ui\AppLogo(), $oPage->getStatusMessagesBlock()]);

 $oPage->addItem(new \Ease\TWB5\Container($setupRow));

$oPage->addItem(new Ui\PageBottom());

echo $oPage->draw();
