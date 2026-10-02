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

use AbraFlexi\Acceptor\Installer\Wizard;

/**
 * System.Spoje.Net - WebHook Acceptor installer wizard.
 *
 * Step 1: AbraFlexi REST API endpoint, Step 2: credentials, Step 3: company.
 *
 * @author     Vítězslav Dvořák <vitex@vitexsoftware.com>
 * @copyright  2017-2026 Spoje.Net, 2021-2026 VitexSoftware
 */
\define('APP_NAME', 'WebHookInstaller');
\define('EASE_LOGGER', 'syslog');

require_once __DIR__.'/../vendor/autoload.php';

$envFile = '../.env'; // rewritten to /etc/abraflexi-webhook-acceptor/.env by debian/rules
\Ease\Shared::init(['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'], $envFile);

$oPage = new Ui\InstallerPage(_('Installation wizard'), _('Connect the WebHook acceptor to your AbraFlexi in three steps.'));
$wizard = new Wizard();

$hookurl = str_replace(basename(__FILE__), 'webhook.php', \Ease\Document::phpSelf());
$stepTitles = [
    Wizard::STEP_ENDPOINT => _('AbraFlexi endpoint'),
    Wizard::STEP_CREDENTIALS => _('Credentials'),
    Wizard::STEP_COMPANY => _('Company'),
];

$requested = (int) ($_REQUEST['step'] ?? 1);
$step = $wizard->clampStep($requested);
$done = false;

if (isset($_GET['restart'])) {
    $wizard->reset();
    header('Location: '.basename(__FILE__));

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A POST is only valid for the step the user is allowed to be on
    $step = $wizard->clampStep((int) ($_POST['step'] ?? 1));

    try {
        if (!$wizard->checkCsrfToken($_POST['csrf'] ?? null)) {
            throw new \InvalidArgumentException(_('Invalid form token, please try again'));
        }

        switch ($step) {
            case Wizard::STEP_ENDPOINT:
                $wizard->verifyEndpoint((string) ($_POST['url'] ?? ''));
                header('Location: '.basename(__FILE__).'?step=2');

                exit;
            case Wizard::STEP_CREDENTIALS:
                if (isset($_POST['back'])) {
                    header('Location: '.basename(__FILE__).'?step=1');

                    exit;
                }

                $wizard->verifyCredentials($_POST, ($_POST['method'] ?? 'password') === 'apikey');
                header('Location: '.basename(__FILE__).'?step=3');

                exit;
            case Wizard::STEP_COMPANY:
                if (isset($_POST['back'])) {
                    header('Location: '.basename(__FILE__).'?step=2');

                    exit;
                }

                $registered = $wizard->install((string) ($_POST['company'] ?? ''), $hookurl, $envFile);
                $oPage->addStatusMessage(sprintf(_('Hook %s was registered'), $registered), 'success');
                $wizard->reset();
                $done = true;

                break;
        }
    } catch (\InvalidArgumentException $exc) {
        $oPage->addStatusMessage($exc->getMessage(), 'warning');
    } catch (\Exception $exc) {
        $oPage->addStatusMessage($exc->getMessage(), 'error');
    }
}

// Step indicator
$nav = new \Ease\Html\UlTag(null, ['class' => 'nav nav-pills nav-fill mb-4 installer-steps']);

foreach ($stepTitles as $no => $title) {
    $class = 'nav-link'.($no === $step ? ' active' : '').(!$done && $no > $wizard->maxAllowedStep() ? ' disabled' : '');
    $nav->addItem(new \Ease\Html\LiTag(new \Ease\Html\ATag($no <= $wizard->maxAllowedStep() && !$done ? basename(__FILE__).'?step='.$no : '#', $no.'. '.$title, ['class' => $class]), ['class' => 'nav-item']));
}

$form = new \Ease\TWB5\Form(['method' => 'post', 'action' => basename(__FILE__).'?step='.$step]);
$form->addItem(new \Ease\Html\InputHiddenTag('step', (string) $step));
$form->addItem(new \Ease\Html\InputHiddenTag('csrf', $wizard->getCsrfToken()));

if ($done) {
    $form = new \Ease\Html\DivTag([
        new \Ease\Html\H2Tag(_('Done')),
        new \Ease\Html\PTag(sprintf(_('WebHook was registered and the configuration saved to %s'), basename($envFile))),
        new \Ease\TWB5\LinkButton(basename(__FILE__).'?restart=1', _('Run again'), 'secondary'),
    ]);
} else {
    switch ($step) {
        case Wizard::STEP_ENDPOINT:
            $form->addItem(new \Ease\Html\DivTag([
                new \Ease\Html\LabelTag('url', _('RestAPI endpoint url')),
                new \Ease\Html\InputTextTag('url', $_POST['url'] ?? $wizard->get('url', \Ease\Shared::cfg('ABRAFLEXI_URL', '')), ['class' => 'form-control', 'id' => 'url', 'placeholder' => 'https://abraflexi.example.com:5434']),
                new \Ease\Html\SmallTag(_('The URL you use to open AbraFlexi'), ['class' => 'form-text']),
            ], ['class' => 'mb-4']));
            $form->addItem(new \Ease\Html\ButtonTag(_('Verify endpoint and continue'), ['type' => 'submit', 'class' => 'btn btn-primary']));

            break;
        case Wizard::STEP_CREDENTIALS:
            $apikey = ($_POST['method'] ?? 'password') === 'apikey';
            $form->addItem(new \Ease\Html\DivTag([
                new \Ease\Html\LabelTag('method', _('Sign in using')),
                new \Ease\Html\SelectTag('method', ['password' => _('Login and password'), 'apikey' => _('API key (authSessionId)')], $apikey ? 'apikey' : 'password', ['class' => 'form-select', 'id' => 'method', 'onchange' => "document.getElementById('pw').hidden=this.value!=='password';document.getElementById('ak').hidden=this.value!=='apikey';"]),
            ], ['class' => 'mb-3']));

            $pwBlock = new \Ease\Html\DivTag(null, ['id' => 'pw']);
            $pwBlock->addItem(new \Ease\Html\DivTag([new \Ease\Html\LabelTag('user', _('REST API Username')), new \Ease\Html\InputTextTag('user', $_POST['user'] ?? $wizard->get('user', \Ease\Shared::cfg('ABRAFLEXI_LOGIN', '')), ['class' => 'form-control'])], ['class' => 'mb-3']));
            $pwBlock->addItem(new \Ease\Html\DivTag([new \Ease\Html\LabelTag('password', _('Rest API Password')), new \Ease\Html\InputPasswordTag('password', '', ['class' => 'form-control'])], ['class' => 'mb-3']));
            $akBlock = new \Ease\Html\DivTag(new \Ease\Html\DivTag([new \Ease\Html\LabelTag('authSessionId', _('API key')), new \Ease\Html\InputPasswordTag('authSessionId', '', ['class' => 'form-control'])], ['class' => 'mb-3']), ['id' => 'ak']);

            if ($apikey) {
                $pwBlock->setTagProperty('hidden', 'hidden');
            } else {
                $akBlock->setTagProperty('hidden', 'hidden');
            }

            $form->addItem($pwBlock);
            $form->addItem($akBlock);
            $form->addItem(new \Ease\Html\ButtonTag(_('Back'), ['type' => 'submit', 'name' => 'back', 'value' => '1', 'class' => 'btn btn-outline-secondary me-2', 'formnovalidate' => 'formnovalidate']));
            $form->addItem(new \Ease\Html\ButtonTag(_('Verify login and continue'), ['type' => 'submit', 'class' => 'btn btn-primary']));

            break;
        case Wizard::STEP_COMPANY:
            try {
                $companies = $wizard->listCompanies();
            } catch (\Exception $exc) {
                $companies = [];
                $oPage->addStatusMessage($exc->getMessage(), 'error');
            }

            if (empty($companies)) {
                $oPage->addStatusMessage(_('No company available for this user'), 'warning');
            }

            $form->addItem(new \Ease\Html\DivTag([
                new \Ease\Html\LabelTag('company', _('Company')),
                new \Ease\Html\SelectTag('company', $companies, (string) $wizard->get('company', \Ease\Shared::cfg('ABRAFLEXI_COMPANY', '')), ['class' => 'form-select', 'id' => 'company']),
            ], ['class' => 'mb-3']));
            $form->addItem(new \Ease\Html\ButtonTag(_('Back'), ['type' => 'submit', 'name' => 'back', 'value' => '1', 'class' => 'btn btn-outline-secondary me-2', 'formnovalidate' => 'formnovalidate']));
            $form->addItem(new \Ease\Html\ButtonTag(_('Install WebHook'), ['type' => 'submit', 'class' => 'btn btn-success'.($companies ? '' : ' disabled')]));

            break;
    }
}

if (!$done && $step === Wizard::STEP_ENDPOINT && !$_POST) {
    $oPage->addStatusMessage(_('WebHook Acceptor URL').': '.\dirname(\Ease\WebPage::phpSelf()));
}

if (\array_key_exists('REMOTE_HOST', $_SERVER) === false) {
    $_SERVER['REMOTE_HOST'] = $_SERVER['REMOTE_ADDR'] ?? '';

    switch ($_SERVER['SERVER_SOFTWARE'] ?? '') {
        case 'Apache':
            $oPage->addStatusMessage(_('Add "HostnameLookups On" to your Apache configuration'), 'warning');

            break;
        case 'nginx':
            $_SERVER['REMOTE_HOST'] = gethostbyaddr($_SERVER['REMOTE_ADDR']);

            break;

        default:
            $oPage->addStatusMessage(_('REMOTE_HOST is not set. Is HostnameLookups On ?'), 'warning');
    }
}

$setupRow = new \Ease\TWB5\Row(null, 0, ['class' => 'g-4']);
$setupRow->addColumn(7, new \Ease\Html\DivTag(new \Ease\Html\DivTag([$done ? null : $nav, $form], ['class' => 'card-body']), ['class' => 'card']));
$setupRow->addColumn(5, new \Ease\Html\DivTag([new Ui\AppLogo(), $oPage->getStatusMessagesBlock()], ['class' => 'installer-side']));

$oPage->content->addItem($setupRow);

echo $oPage->draw();
