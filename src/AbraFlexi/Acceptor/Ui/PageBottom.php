<?php

/**
 * AbraFlexi WebHook Acceptor  - Shared page bottom class
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2021-2024 Vitex Software
 */

namespace AbraFlexi\Acceptor\Ui;

/**
 * Page Bottom
 *
 * @package    VitexSoftware
 * @author     Vitex <vitex@hippy.cz>
 */
class PageBottom extends \Ease\Html\FooterTag
{
    /**
     * Zobrazí přehled právě přihlášených a spodek stránky
     */
    public function finalize()
    {
        $composer = 'composer.json';
        if (!file_exists($composer)) {
            $composer = '../debian/conf/' . $composer;
        }

        $container = $this->setTagID('footer');

//        if (\Ease\Shared::user()->getUserID()) {
//        $this->addItem(new \Ease\ui\BrowsingHistory());
//        }
        $this->addItem('<hr>');
        $footrow = new \Ease\TWB4\Row();

        $author = '<a href="https://github.com/VitexSoftware/abraflexi-webhook-acceptor">AbraFlexi Webhook Acceptor</a> v.: ' . \Ease\Shared::appVersion() . '&nbsp;&nbsp; &copy; 2020-2022 <a href="https://vitexsoftware.com/">Vitex Software</a>';

        $footrow->addColumn(6, [$author]);

        $this->addItem(new \Ease\TWB4\Container($footrow));
    }
}
