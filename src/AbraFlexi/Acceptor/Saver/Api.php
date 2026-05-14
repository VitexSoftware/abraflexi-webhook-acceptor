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
 * Description of Api.
 *
 * @author vitex
 */
class Api implements saver
{
    private string $company = '';
    private string $url = '';

    public function setCompany(string $companyCode): void
    {
        $this->company = $companyCode;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function saveWebhookData(array $changes): int
    {
        // TODO: Implement API push
        return 0;
    }

    public function getLastProcessedVersion(): ?int
    {
        return null;
    }

    public function saveLastProcessedVersion(int $version): int
    {
        return $version;
    }
}
