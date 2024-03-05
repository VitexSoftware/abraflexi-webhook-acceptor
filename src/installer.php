<?php

namespace AbraFlexi\Acceptor;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@vitexsoftware.com>
 * @copyright  2017-2020 Spoje.Net, 2021-2024 VitexSoftware
 */

define('APP_NAME', 'WebHookInstaller');
define('EASE_LOGGER', 'syslog');
require_once __DIR__ . '/../vendor/autoload.php';

\Ease\Shared::init(['DB_CONNECTION','DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD'], '../.env');

$success = false;
$hookurl = str_replace(basename(__FILE__), 'webhook.php', \Ease\Document::phpSelf());
$oPage = new \Ease\TWB4\WebPage(_('WebHook acceptor installer'));

$baseUrl = dirname(\Ease\WebPage::phpSelf());

$loginForm = new \AbraFlexi\ui\TWB4\ConnectionForm(['action' => 'install.php']);

//$loginForm->addInput( new \Ease\Html\InputUrlTag('myurl'), _('My Url'), dirname(\Ease\Page::phpSelf()), sprintf( _('Same url as you can see in browser without %s'), basename( __FILE__ ) ) );

$loginForm->fillUp(\Ease\WebPage::isPosted() ? $_REQUEST : \Ease\Shared::instanced()->configuration);

$loginForm->addItem(new \Ease\TWB4\SubmitButton(_('Install WebHook'), 'success btn-lg btn-block'));

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
    $oPage->addStatusMessage(_('WebHook Acceptor URL') . ': ' . $baseUrl);
}

$setupRow = new \Ease\TWB4\Row();
if ($success) {
    $setupRow->addColumn(6, new \Ease\Html\H2Tag(_('Done')));
} else {
    $setupRow->addColumn(6, $loginForm);
}
$setupRow->addColumn(6, [new Ui\AppLogo(), $oPage->getStatusMessagesBlock()]);

$oPage->addItem(new \Ease\TWB4\Container($setupRow));

$oPage->addItem(new Ui\PageBottom());

echo $oPage;
