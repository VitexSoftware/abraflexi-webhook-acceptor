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
 * Page Bottom.
 *
 * @author     Vitex <vitex@hippy.cz>
 */
class PageBottom extends \Ease\Html\FooterTag
{
    /**
     * Zobrazí přehled právě přihlášených a spodek stránky.
     */
    public function finalize(): void
    {
        if ($this->isFinalized() === false) {
            $container = $this->setTagID('footer');

            $this->addItem('<hr>');
            $footrow = new \Ease\TWB5\Row();

            $author = '<a href="https://github.com/VitexSoftware/abraflexi-webhook-acceptor">AbraFlexi Webhook Acceptor</a> v.: '.\Ease\Shared::appVersion().'&nbsp;&nbsp; &copy; 2020-2025 <a href="https://vitexsoftware.com/">Vitex Software</a>';

            $footrow->addColumn(6, [$author]);

            $this->addItem(new \Ease\TWB5\Container($footrow));

            parent::finalize();
        }
    }
}
