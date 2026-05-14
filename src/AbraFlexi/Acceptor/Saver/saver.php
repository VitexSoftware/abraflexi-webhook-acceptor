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

    /**
     * Save webhook data to the backend.
     *
     * @param array $changes Array of change records from AbraFlexi
     *
     * @return int Last processed change version ID
     */
    public function saveWebhookData(array $changes): int;

    /**
     * Retrieve last processed version from the backend.
     *
     * @return int|null Version number, or null if backend does not track state
     */
    public function getLastProcessedVersion(): ?int;

    /**
     * Persist the last processed version marker.
     *
     * @param int $version The version to store
     *
     * @return int The stored version
     */
    public function saveLastProcessedVersion(int $version): int;
}
