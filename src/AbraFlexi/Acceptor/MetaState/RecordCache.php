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
 * Generic per-version document cache for any AbraFlexi evidence.
 *
 * Each webhook-triggered download of a document produces one row, keyed by
 * (inversion, recordid, evidence, serverurl). Multiple rows for the same
 * document accumulate over time and can be retrieved newest-first via
 * loadAll(), giving callers access to the full retained history.
 *
 * The json column carries a FULLTEXT index so the raw JSON is searchable
 * without prior deserialisation.
 *
 * @author vitex
 */
class RecordCache extends \Ease\SQL\Engine
{
    public string $myTable = 'record_cache';

    public string $keyColumn = 'id';

    /**
     * Persist a new document snapshot for the given change version.
     *
     * @param array<string, mixed> $data Full document data as returned by AbraFlexi
     */
    public function store(int $inversion, int $recordId, string $evidence, string $serverUrl, array $data): void
    {
        $this->insertToSQL([
            'inversion' => $inversion,
            'recordid' => $recordId,
            'evidence' => $evidence,
            'serverurl' => $serverUrl,
            'json' => json_encode($data, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES),
        ]);
    }

    /**
     * Load the most recent snapshot for a document (current state).
     *
     * @return null|array<string, mixed>
     */
    public function loadLatest(int $recordId, string $evidence, string $serverUrl): ?array
    {
        return $this->loadNthVersion($recordId, $evidence, $serverUrl, 0);
    }

    /**
     * Load the snapshot immediately preceding the current one (previous state).
     *
     * @return null|array<string, mixed>
     */
    public function loadPrevious(int $recordId, string $evidence, string $serverUrl): ?array
    {
        return $this->loadNthVersion($recordId, $evidence, $serverUrl, 1);
    }

    /**
     * Load all retained snapshots for a document, newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function loadAll(int $recordId, string $evidence, string $serverUrl): array
    {
        $rows = $this->getFluentPDO()
            ->from($this->myTable)
            ->select('json, inversion, created')
            ->where('recordid', $recordId)
            ->where('evidence', $evidence)
            ->where('serverurl', $serverUrl)
            ->orderBy('inversion DESC')
            ->fetchAll();

        return array_map(
            static fn (array $row): array => array_merge(
                json_decode($row['json'], true) ?? [],
                ['_inversion' => $row['inversion'], '_created' => $row['created']],
            ),
            $rows ?: [],
        );
    }

    /**
     * @return null|array<string, mixed>
     */
    private function loadNthVersion(int $recordId, string $evidence, string $serverUrl, int $offset): ?array
    {
        $row = $this->getFluentPDO()
            ->from($this->myTable)
            ->select('json')
            ->where('recordid', $recordId)
            ->where('evidence', $evidence)
            ->where('serverurl', $serverUrl)
            ->orderBy('inversion DESC')
            ->limit(1)
            ->offset($offset)
            ->fetch();

        if (empty($row['json'])) {
            return null;
        }

        $decoded = json_decode($row['json'], true);

        return \is_array($decoded) ? $decoded : null;
    }
}
