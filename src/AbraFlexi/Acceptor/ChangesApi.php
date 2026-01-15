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
 * Description of Api.
 *
 * @author vitex
 * @copyright  2017-2024 Spoje.Net, 2025-2026 VitexSoftware
 */
class ChangesApi extends \Ease\SQL\Engine
{
    public string $myTable = 'changesapi';
}
