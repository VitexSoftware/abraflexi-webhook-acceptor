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

namespace AbraFlexi\Acceptor\MetaState;

/**
 * Best-effort cache of the invoice fields relevant to metastate detection.
 * Not an authoritative source of truth: if a webhook is missed (network
 * outage, Apache restart), this cache lags reality and the resolver simply
 * will not emit a metastate for the skipped change.
 *
 * @author vitex
 */
class InvoiceStateCache extends \Ease\SQL\Engine
{
    public string $myTable = 'invoice_state_cache';

    /**
     * Load the last known relevant field values for a document.
     *
     * @return null|array<string, mixed>
     */
    public function load(int $recordId, string $evidence, string $serverUrl): ?array
    {
        $rows = $this->getColumnsFromSQL(
            ['zbyvauhradit', 'datuhrady', 'datup1', 'datup2', 'datsmir', 'datpenale', 'storno', 'poznam'],
            ['recordid' => $recordId, 'evidence' => $evidence, 'serverurl' => $serverUrl],
        );

        return $rows[0] ?? null;
    }

    /**
     * Persist the current field values as the new "last known" state.
     *
     * @param array<string, mixed> $state
     */
    public function store(int $recordId, string $evidence, string $serverUrl, array $state): void
    {
        $key = ['recordid' => $recordId, 'evidence' => $evidence, 'serverurl' => $serverUrl];
        $existing = $this->load($recordId, $evidence, $serverUrl);

        if (null === $existing) {
            $this->insertToSQL(array_merge($key, $state));
        } else {
            $this->updateToSQL($state, $key);
        }
    }
}
