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

namespace AbraFlexi\Acceptor\Ui;

/**
 * Installer page in the Vitex Software look (see https://vitexsoftware.cz).
 *
 * @author Vítězslav Dvořák <vitex@vitexsoftware.com>
 */
class InstallerPage extends \Ease\TWB5\WebPage
{
    /**
     * Bump when css/installer.css or js/installer.js change, so browsers fetch the new version.
     */
    public const ASSET_VERSION = '1.0.0';
    public \Ease\Html\DivTag $content;

    public function __construct(string $title, string $lead = '')
    {
        parent::__construct($title);

        // Light/dark mode before the first paint, so the page does not flash (dark is the default).
        $this->head->addItem('<script>(function(){var t="dark";try{t=localStorage.getItem("vsTheme")||t}catch(e){}var d=document.documentElement;d.setAttribute("data-theme",t);d.setAttribute("data-bs-theme",t)})()</script>');
        $this->head->addItem('<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>');
        $this->includeCss('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&display=swap');
        $this->includeCss('css/installer.css?v='.self::ASSET_VERSION);
        $this->includeJavaScript('js/installer.js?v='.self::ASSET_VERSION);
        $this->head->addItem('<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">');
        $this->head->addItem('<meta name="theme-color" content="#1d1440">');

        $this->addItem('<header class="site-header"><div class="bar"><a class="brand" href="https://vitexsoftware.com/"><img src="img/vstux.png" alt="" width="36" height="36"><span class="brand-name"><b>Vitex</b> Software</span></a>'
            .'<div class="header-controls"><button type="button" class="icon-btn theme-toggle" aria-label="'._('Switch light / dark mode').'" title="'._('Switch light / dark mode').'">'
            .'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.2M12 19.3v2.2M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/></svg></button></div></div></header>');

        $hero = new \Ease\Html\DivTag(null, ['class' => 'page-hero']);
        $inner = $hero->addItem(new \Ease\Html\DivTag(null, ['class' => 'page-hero-inner']));
        $inner->addItem(new \Ease\Html\DivTag(_('AbraFlexi WebHook Acceptor'), ['class' => 'eyebrow']));
        $inner->addItem(new \Ease\Html\H1Tag($title));

        if ($lead !== '') {
            $inner->addItem(new \Ease\Html\PTag($lead, ['class' => 'lead']));
        }

        $this->addItem($hero);
        $this->content = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'page-content']));
    }

    public function finalize(): void
    {
        if ($this->isFinalized() === false) {
            $this->addItem('<footer class="site-footer"><span><a href="https://github.com/VitexSoftware/abraflexi-webhook-acceptor">AbraFlexi Webhook Acceptor</a> v.: '.\Ease\Shared::appVersion().'</span><span>&copy; 2020-2026 <a href="https://vitexsoftware.com/">Vitex Software</a></span></footer>');
        }

        parent::finalize();
    }
}
