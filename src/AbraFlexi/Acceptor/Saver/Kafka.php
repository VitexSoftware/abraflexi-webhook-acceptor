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
 * Kafka storage backend.
 *
 * Produces webhook change events as JSON messages to per-evidence topics.
 * Topic naming: {KAFKA_TOPIC_PREFIX}-{evidence}
 *
 * @author vitex
 */
class Kafka implements saver
{
    private string $company = '';
    private string $url = '';
    private string $brokers;
    private string $topicPrefix;
    private ?\RdKafka\Producer $producer = null;

    public function __construct()
    {
        $this->brokers = \Ease\Shared::cfg('KAFKA_BROKERS', 'localhost:9092');
        $this->topicPrefix = \Ease\Shared::cfg('KAFKA_TOPIC_PREFIX', 'abraflexi');
    }

    public function __destruct()
    {
        if ($this->producer !== null) {
            $this->producer->flush(10000);
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
        $producer = $this->getProducer();
        $lastVersion = 0;

        foreach ($changes as $change) {
            $evidence = $change['@evidence'] ?? 'unknown';
            $topicName = $this->topicPrefix.'-'.$evidence;
            $topic = $producer->newTopic($topicName);

            $message = json_encode([
                'company' => $this->company,
                'url' => $this->url,
                '@in-version' => $change['@in-version'],
                'id' => $change['id'],
                '@evidence' => $evidence,
                '@operation' => $change['@operation'],
                'external-ids' => $change['external-ids'] ?? [],
            ], \JSON_THROW_ON_ERROR);

            $topic->produce(\RD_KAFKA_PARTITION_UA, 0, $message);
            $producer->poll(0);

            $lastVersion = (int) $change['@in-version'];
        }

        return $lastVersion;
    }

    public function getLastProcessedVersion(): ?int
    {
        return null;
    }

    public function saveLastProcessedVersion(int $version): int
    {
        return $version;
    }

    private function getProducer(): \RdKafka\Producer
    {
        if ($this->producer === null) {
            $conf = new \RdKafka\Conf();
            $conf->set('metadata.broker.list', $this->brokers);
            $this->producer = new \RdKafka\Producer($conf);
        }

        return $this->producer;
    }
}
