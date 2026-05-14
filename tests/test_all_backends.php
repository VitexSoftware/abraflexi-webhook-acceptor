<?php

declare(strict_types=1);

/**
 * Test script to load all webhook JSON fixtures into each storage backend.
 *
 * Usage: php tests/test_all_backends.php
 */

namespace AbraFlexi\Acceptor;

\define('APP_NAME', 'BackendTest');
\define('EASE_LOGGER', 'console');

require_once __DIR__.'/../vendor/autoload.php';

\Ease\Shared::init(['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'ABRAFLEXI_COMPANY'], __DIR__.'/../.env', true);

$_SERVER['REMOTE_HOST'] = 'localhost';
$_SERVER['REQUEST_SCHEME'] = 'cli';

$backends = ['PdoSQL', 'Kafka', 'Redis', 'MongoDB'];
$hooksDir = __DIR__.'/hooks';
$hookFiles = array_filter(scandir($hooksDir), fn ($f) => $f[0] !== '.');

echo "\n=== AbraFlexi WebHook Backend Test ===\n";
echo "Hook files: ".\count($hookFiles)."\n";
echo "Backends to test: ".implode(', ', $backends)."\n\n";

$results = [];

foreach ($backends as $backend) {
    echo str_repeat('-', 60)."\n";
    echo "Testing backend: {$backend}\n";
    echo str_repeat('-', 60)."\n";

    try {
        $saverClass = '\\AbraFlexi\\Acceptor\\Saver\\'.$backend;

        if ($backend === 'PdoSQL') {
            $saver = new $saverClass([]);
        } else {
            $saver = new $saverClass();
        }

        $saver->setCompany('testcompany');
        $saver->setUrl('cli://localhost:5434');

        $loaded = 0;
        $errors = 0;
        $totalChanges = 0;

        // Reset version tracking
        $saver->saveLastProcessedVersion(0);

        foreach ($hookFiles as $hookfile) {
            $inputJSON = file_get_contents($hooksDir.'/'.$hookfile);
            $input = json_decode($inputJSON, true);

            if (empty($input) || !isset($input['winstrom']['changes'])) {
                ++$errors;
                continue;
            }

            $changes = $input['winstrom']['changes'];
            $totalChanges += \count($changes);

            $result = $saver->saveWebhookData($changes);

            if ($result > 0) {
                ++$loaded;
            } else {
                ++$errors;
            }
        }

        $lastVer = $saver->getLastProcessedVersion();
        echo "  Files loaded: {$loaded} | Errors: {$errors} | Total changes: {$totalChanges}\n";
        echo "  Last processed version: ".($lastVer ?? 'null (stateless)')."\n";
        $results[$backend] = ['status' => $errors === 0 ? 'OK' : 'PARTIAL', 'loaded' => $loaded, 'errors' => $errors, 'changes' => $totalChanges];
    } catch (\Throwable $e) {
        echo "  EXCEPTION: ".$e->getMessage()."\n";
        echo "  at ".$e->getFile().":".$e->getLine()."\n";
        $results[$backend] = ['status' => 'FAIL', 'loaded' => 0, 'errors' => 1, 'exception' => $e->getMessage()];
    }

    echo "\n";
}

// Summary
echo "\n".str_repeat('=', 60)."\n";
echo "SUMMARY\n";
echo str_repeat('=', 60)."\n";
printf("%-12s %-8s %-8s %-8s %-10s\n", 'Backend', 'Status', 'Files', 'Errors', 'Changes');
echo str_repeat('-', 50)."\n";

foreach ($results as $backend => $r) {
    printf("%-12s %-8s %-8d %-8d %-10s\n", $backend, $r['status'], $r['loaded'], $r['errors'], $r['changes'] ?? '-');
}

echo "\n";

// Verify data in backends
echo "=== Verification ===\n";

// Redis verification
if (\extension_loaded('redis')) {
    $redis = new \Redis();
    $redis->connect(
        \Ease\Shared::cfg('REDIS_HOST', 'localhost'),
        (int) \Ease\Shared::cfg('REDIS_PORT', '6379'),
    );
    $keys = $redis->keys(\Ease\Shared::cfg('REDIS_KEY_PREFIX', 'abraflexi').':*');
    echo "Redis streams/keys: ".\count($keys)."\n";

    foreach ($keys as $key) {
        $type = $redis->type($key);

        if ($type === \Redis::REDIS_STREAM) {
            echo "  {$key}: ".($redis->xLen($key))." entries\n";
        } else {
            echo "  {$key}: ".$redis->get($key)."\n";
        }
    }
}

// MongoDB verification
if (\extension_loaded('mongodb')) {
    $client = new \MongoDB\Client(\Ease\Shared::cfg('MONGODB_URI', 'mongodb://localhost:27017'));
    $db = $client->selectDatabase(\Ease\Shared::cfg('MONGODB_DATABASE', 'abraflexi_webhook_test'));
    $collections = iterator_to_array($db->listCollections());
    echo "\nMongoDB collections: ".\count($collections)."\n";

    foreach ($collections as $coll) {
        $count = $db->selectCollection($coll->getName())->countDocuments();
        echo "  {$coll->getName()}: {$count} documents\n";
    }
}

// Kafka verification (topic list)
echo "\nKafka topics (via shell):\n";
$output = shell_exec('/opt/kafka/bin/kafka-topics.sh --list --bootstrap-server localhost:9092 2>/dev/null');
$topics = array_filter(explode("\n", trim($output ?? '')));

foreach ($topics as $topic) {
    if (str_starts_with($topic, \Ease\Shared::cfg('KAFKA_TOPIC_PREFIX', 'abraflexi'))) {
        echo "  {$topic}\n";
    }
}

echo "\nDone.\n";
