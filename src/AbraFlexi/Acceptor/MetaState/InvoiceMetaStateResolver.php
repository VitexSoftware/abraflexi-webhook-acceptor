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
 * Derives business metastates (settled, remind1/2/3, penalised, storno,
 * inventory) for invoices from a raw AbraFlexi "update" change notification.
 *
 * On each "update" webhook the full invoice header is downloaded from
 * AbraFlexi and stored as a new row in record_cache, keyed by the AbraFlexi
 * change version number (inversion). The previous version row from the same
 * cache is then loaded and the two JSON snapshots are decoded and diffed in
 * PHP. This keeps the DB schema evidence-agnostic and preserves every field
 * (firma, kod, …) for downstream consumers without an extra API call.
 *
 * Field mapping:
 *   datUp1/datUp2/datSmir/datPenale → remind1/remind2/remind3/penalised
 *   "inventory" is detected via a new "Inventarizace:<date>" line in poznam
 *
 * @author vitex
 */
class InvoiceMetaStateResolver
{
    /**
     * Evidence names this resolver handles.
     */
    public const SUPPORTED_EVIDENCE = ['faktura-vydana', 'faktura-prijata'];

    private RecordCache $recordCache;

    private ?string $lastCustomerCode = null;

    public function __construct(?RecordCache $recordCache = null)
    {
        $this->recordCache = $recordCache ?? new RecordCache();
    }

    public function supports(string $evidence, string $operation): bool
    {
        return 'update' === $operation && \in_array($evidence, self::SUPPORTED_EVIDENCE, true);
    }

    /**
     * Returns the customer code (firma) from the last successfully resolved
     * invoice, or null when no resolution has been performed yet.
     */
    public function getLastCustomerCode(): ?string
    {
        return $this->lastCustomerCode;
    }

    /**
     * Download the current invoice, store it in record_cache under the given
     * inversion, diff against the previous cached version, and return the
     * detected metastate (or null when nothing meaningful changed or on first
     * sighting of the document).
     *
     * @param int $inversion AbraFlexi change version from the webhook (@in-version)
     */
    public function resolve(int $recordId, string $evidence, string $serverUrl, int $inversion): ?string
    {
        $current = $this->fetchCurrentState($recordId, $evidence);

        if (null === $current) {
            return null;
        }

        $previous = $this->recordCache->loadLatest($recordId, $evidence, $serverUrl);
        $this->recordCache->store($inversion, $recordId, $evidence, $serverUrl, $current);

        $firma = $current['firma'] ?? null;
        $this->lastCustomerCode = match (true) {
            $firma === null => null,
            is_array($firma) => ($firma['value'] ?? null) ?: null,
            is_object($firma) => method_exists($firma, '__toString') ? (string) $firma : null,
            default => (string) $firma ?: null,
        };

        if (null === $previous) {
            return null;
        }

        return $this->detectMetaState($previous, $current);
    }

    /**
     * @param array<string, mixed> $previous
     * @param array<string, mixed> $current
     */
    private function detectMetaState(array $previous, array $current): ?string
    {
        if (empty($previous['storno']) && !empty($current['storno'])) {
            return 'storno';
        }

        // settled: remaining amount dropped to zero regardless of whether
        // AbraFlexi populated datUhrady (match_payment via banka does not set it)
        if ((float) ($previous['zbyvaUhradit'] ?? 0) > 0 && (float) ($current['zbyvaUhradit'] ?? 0) === 0.0) {
            return 'settled';
        }

        if (empty($previous['datUp1']) && !empty($current['datUp1'])) {
            return 'remind1';
        }

        if (empty($previous['datUp2']) && !empty($current['datUp2'])) {
            return 'remind2';
        }

        if (empty($previous['datSmir']) && !empty($current['datSmir'])) {
            return 'remind3';
        }

        if (empty($previous['datPenale']) && !empty($current['datPenale'])) {
            return 'penalised';
        }

        if ($this->hasNewInventoryNote((string) ($previous['poznam'] ?? ''), (string) ($current['poznam'] ?? ''))) {
            return 'inventory';
        }

        return null;
    }

    /**
     * True when $current's poznam contains an "Inventarizace:<date>" line
     * that is not present in $previous.
     */
    private function hasNewInventoryNote(string $previous, string $current): bool
    {
        $previousLines = array_flip(explode("\n", $previous));

        foreach (explode("\n", $current) as $line) {
            if (str_contains($line, 'Inventarizace:') && !isset($previousLines[$line])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fetch relevant invoice header fields from AbraFlexi.
     *
     * Returns the full field set needed for metastate detection plus firma
     * and kod for document identification. Does not fetch line items.
     *
     * @return null|array<string, mixed>
     */
    private function fetchCurrentState(int $recordId, string $evidence): ?array
    {
        $invoicer = new \AbraFlexi\FakturaVydana(null, ['evidence' => $evidence]);
        $rows = $invoicer->getColumnsFromAbraFlexi(
            ['kod', 'firma', 'zbyvaUhradit', 'datUhrady', 'datUp1', 'datUp2', 'datSmir', 'datPenale', 'storno', 'poznam'],
            ['id' => $recordId],
        );

        return $rows[0] ?? null;
    }
}
