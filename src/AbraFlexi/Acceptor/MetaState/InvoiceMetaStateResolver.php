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
 * inventory) for invoices from a raw AbraFlexi "update" change notification,
 * by fetching the invoice's current state and diffing it against the last
 * known state cached by InvoiceStateCache.
 *
 * Field mapping is taken 1:1 from the (unused) abraflexi-changes-processor's
 * FakturaVydana::getMetaState(): datUp1/datUp2/datSmir/datPenale ->
 * remind1/remind2/remind3/penalised. The "inventory" metastate follows
 * abraflexi-reminder's Upominac, which - unlike the numbered reminders -
 * does not use a dedicated date column: it appends an "Inventarizace:<date>"
 * line to the `poznam` note field (see Upominac::posliUpominku() and
 * Upominac::getDaysToLastInventarization()).
 *
 * @author vitex
 */
class InvoiceMetaStateResolver
{
    /**
     * Evidence names this resolver handles.
     */
    public const SUPPORTED_EVIDENCE = ['faktura-vydana', 'faktura-prijata'];

    private InvoiceStateCache $stateCache;

    public function __construct(?InvoiceStateCache $stateCache = null)
    {
        $this->stateCache = $stateCache ?? new InvoiceStateCache();
    }

    public function supports(string $evidence, string $operation): bool
    {
        return 'update' === $operation && \in_array($evidence, self::SUPPORTED_EVIDENCE, true);
    }

    /**
     * Fetch the invoice's current relevant fields, diff against the cached
     * previous state, persist the new state, and return the detected
     * metastate (or null when nothing meaningful changed).
     */
    public function resolve(int $recordId, string $evidence, string $serverUrl): ?string
    {
        $current = $this->fetchCurrentState($recordId, $evidence);

        if (null === $current) {
            return null;
        }

        $previous = $this->stateCache->load($recordId, $evidence, $serverUrl);
        $this->stateCache->store($recordId, $evidence, $serverUrl, $current);

        if (null === $previous) {
            // First time we see this invoice: nothing to diff against yet.
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

        if (empty($previous['datuhrady']) && !empty($current['datuhrady']) && (float) ($current['zbyvauhradit'] ?? 0) === 0.0) {
            return 'settled';
        }

        if (empty($previous['datup1']) && !empty($current['datup1'])) {
            return 'remind1';
        }

        if (empty($previous['datup2']) && !empty($current['datup2'])) {
            return 'remind2';
        }

        if (empty($previous['datsmir']) && !empty($current['datsmir'])) {
            return 'remind3';
        }

        if (empty($previous['datpenale']) && !empty($current['datpenale'])) {
            return 'penalised';
        }

        if ($this->hasNewInventoryNote((string) ($previous['poznam'] ?? ''), (string) ($current['poznam'] ?? ''))) {
            return 'inventory';
        }

        return null;
    }

    /**
     * True when $current's `poznam` contains an "Inventarizace:<date>" line
     * that is not present in $previous (line-by-line diff, not a substring
     * check, since both states typically already contain older entries).
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
     * @return null|array<string, mixed>
     */
    private function fetchCurrentState(int $recordId, string $evidence): ?array
    {
        $invoicer = new \AbraFlexi\FakturaVydana(null, ['evidence' => $evidence]);
        $rows = $invoicer->getColumnsFromAbraFlexi(
            ['zbyvaUhradit', 'datUhrady', 'datUp1', 'datUp2', 'datSmir', 'datPenale', 'storno', 'poznam'],
            ['id' => $recordId],
        );

        if (empty($rows)) {
            return null;
        }

        $row = $rows[0];

        return [
            'zbyvauhradit' => $row['zbyvaUhradit'] ?? null,
            'datuhrady' => $row['datUhrady'] ?? null,
            'datup1' => $row['datUp1'] ?? null,
            'datup2' => $row['datUp2'] ?? null,
            'datsmir' => $row['datSmir'] ?? null,
            'datpenale' => $row['datPenale'] ?? null,
            'storno' => $row['storno'] ?? null,
            'poznam' => $row['poznam'] ?? null,
        ];
    }
}
