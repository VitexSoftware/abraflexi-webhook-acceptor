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
 * Redis Streams storage backend.
 *
 * Stores webhook changes as Redis Stream entries per evidence type.
 * Stream key naming: {REDIS_KEY_PREFIX}:{evidence}
 * Version tracking via simple key: {REDIS_KEY_PREFIX}:changesapi:{serverurl}:changeid
 *
 * @author vitex
 */
class Redis implements saver
{
    private string $company = '';
    private string $url = '';
    private string $keyPrefix;
    private \Redis $redis;

    public function __construct()
    {
        $host = \Ease\Shared::cfg('REDIS_HOST', 'localhost');
        $port = (int) \Ease\Shared::cfg('REDIS_PORT', '6379');
        $password = \Ease\Shared::cfg('REDIS_PASSWORD', '');
        $this->keyPrefix = \Ease\Shared::cfg('REDIS_KEY_PREFIX', 'abraflexi');

        $this->redis = new \Redis();
        $this->redis->connect($host, $port);

        if (!empty($password)) {
            $this->redis->auth($password);
        }
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

        foreach ($changes as $change) {
            $evidence = $change['@evidence'] ?? 'unknown';
            $streamKey = $this->keyPrefix.':'.$evidence;

            $this->redis->xAdd($streamKey, '*', [
                'inversion' => (string) $change['@in-version'],
                'recordid' => (string) $change['id'],
                'evidence' => $evidence,
                'operation' => $change['@operation'],
                'externalids' => json_encode($change['external-ids'] ?? []),
                'company' => $this->company,
                'url' => $this->url,
            ]);

            $lastVersion = (int) $change['@in-version'];
        }

        if ($lastVersion > 0) {
            $this->saveLastProcessedVersion($lastVersion);
        }

        return $lastVersion;
    }

    public function getLastProcessedVersion(): ?int
    {
        $key = $this->getVersionKey();
        $value = $this->redis->get($key);

        return $value !== false ? (int) $value : null;
    }

    public function saveLastProcessedVersion(int $version): int
    {
        $key = $this->getVersionKey();
        $this->redis->set($key, (string) $version);

        return $version;
    }

    private function getVersionKey(): string
    {
        $serverUrl = $this->url.'/c/'.$this->company;

        return $this->keyPrefix.':changesapi:'.str_replace(['/', ':'], ['_', '_'], $serverUrl).':changeid';
    }
}
