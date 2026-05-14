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
 * MongoDB storage backend.
 *
 * Stores webhook changes in per-evidence collections.
 * Version tracking via 'changesapi' collection with upsert by serverurl.
 *
 * @author vitex
 */
class MongoDB implements saver
{
    private string $company = '';
    private string $url = '';
    private \MongoDB\Database $database;

    public function __construct()
    {
        $uri = \Ease\Shared::cfg('MONGODB_URI', 'mongodb://localhost:27017');
        $dbName = \Ease\Shared::cfg('MONGODB_DATABASE', 'abraflexi_webhook');

        $client = new \MongoDB\Client($uri);
        $this->database = $client->selectDatabase($dbName);
    }

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
        $lastVersion = 0;
        $grouped = [];

        foreach ($changes as $change) {
            $evidence = $change['@evidence'] ?? 'unknown';
            $grouped[$evidence][] = [
                'inversion' => (int) $change['@in-version'],
                'recordid' => (int) $change['id'],
                'evidence' => $evidence,
                'operation' => $change['@operation'],
                'externalids' => $change['external-ids'] ?? [],
                'source' => $this->url.'/c/'.$this->company,
                'target' => 'system',
                'created' => new \MongoDB\BSON\UTCDateTime(),
            ];

            $lastVersion = (int) $change['@in-version'];
        }

        foreach ($grouped as $evidence => $documents) {
            $this->database->selectCollection($evidence)->insertMany($documents);
        }

        if ($lastVersion > 0) {
            $this->saveLastProcessedVersion($lastVersion);
        }

        return $lastVersion;
    }

    public function getLastProcessedVersion(): ?int
    {
        $serverUrl = $this->url.'/c/'.$this->company;
        $result = $this->database->selectCollection('changesapi')->findOne(
            ['serverurl' => $serverUrl],
        );

        return $result !== null ? (int) $result['changeid'] : null;
    }

    public function saveLastProcessedVersion(int $version): int
    {
        $serverUrl = $this->url.'/c/'.$this->company;
        $this->database->selectCollection('changesapi')->updateOne(
            ['serverurl' => $serverUrl],
            ['$set' => ['changeid' => $version, 'serverurl' => $serverUrl]],
            ['upsert' => true],
        );

        return $version;
    }
}
