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

namespace AbraFlexi\Acceptor\Saver;

/**
 * @author vitex
 */
interface saver
{
    /**
     * Keep Current company.
     */
    public function setCompany(string $companyCode);

    /**
     * Keep Current server url.
     */
    public function setUrl(string $url);
}
