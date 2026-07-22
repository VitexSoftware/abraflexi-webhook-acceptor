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
 * Description of PdoSQL.
 *
 * @author vitex
 */
class PdoSQL extends \Ease\SQL\Engine implements saver
{
    public string $myTable = 'changes_cache';
    public $myKeyColumn = 'inversion';
    public $lastProcessedVersion = 0;
    public $company = '';
    public $url = '';
    private $serverUrl = '';

    /**
     * Prijmac WebHooku.
     *
     * @param mixed $options
     */
    public function __construct($options)
    {
        parent::__construct($options);
        $this->setupProperty($options, 'company', 'ABRAFLEXI_COMPANY');
        $this->setupProperty($options, 'url', 'ABRAFLEXI_URL');
    }

    /**
     * Keep current company code.
     */
    public function setCompany(string $companyCode): void
    {
        $this->company = $companyCode;
    }

    /**
     * Keep current server url.
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Nacte posledni zpracovanou verzi.
     *
     * @return int|null $version
     */
    public function getLastProcessedVersion(): ?int
    {
        $this->serverUrl = $this->url.'/c/'.$this->company;
        $lastProcessedVersion = null;
        $this->setmyTable('changesapi');
        $chRaw = $this->getColumnsFromSQL(['changeid'], ['serverurl' => $this->serverUrl]);

        if (isset($chRaw[0]['changeid'])) {
            $lastProcessedVersion = (int) $chRaw[0]['changeid'];
        } else {
            try {
                $this->addStatusMessage(sprintf(_('%s: API Initialized with changeID 0'), $this->serverUrl), $this->saveLastProcessedVersion(0) ? 'success' : 'warning');
                $lastProcessedVersion = 0;
            } catch (\Exception $exc) {
                $this->addStatusMessage(sprintf(_('%s: Last Processed Change ID Loading Failed'), $this->serverUrl), 'warning');
            }
        }

        $this->setmyTable('changes_cache');

        return $lastProcessedVersion;
    }

    /**
     * @return int Last processed version
     */
    public function saveLastProcessedVersion(int $version): int
    {
        $this->serverUrl = $this->url.'/c/'.$this->company;
        //        if ($version) {
        //            $this->lastProcessedVersion = $this->getLastProcessedVersion();
        //        }
        $this->setmyTable('changesapi');
        $this->setKeyColumn('serverurl');
        $apich = ['changeid' => $version, 'serverurl' => $this->serverUrl];

        try {
            if ($version ? $this->updateToSQL($apich) : $this->insertToSQL($apich)) {
                $this->lastProcessedVersion = $version;
            }
        } catch (\Exception $exc) {
            $this->addStatusMessage(_('Last Processed Change ID Saving Failed'), 'error');
        }

        $this->setmyTable('changes_cache');
        $this->setKeyColumn('inversion');

        return $version;
    }

    /**
     * conver $sqlData column names to $jsonData column names.
     *
     * @param array $sqlData
     *
     * @return array
     */
    public static function sqlColsToJsonCols($sqlData)
    {
        $jsonData['@in-version'] = $sqlData['inversion'];
        $jsonData['id'] = $sqlData['recordid'];
        $jsonData['@evidence'] = $sqlData['evidence'];
        $jsonData['@operation'] = $sqlData['operation'];
        $jsonData['external-ids'] = unserialize(stripslashes($sqlData['externalids']));

        return $jsonData;
    }

    /**
     * conver $jsonData column names to $sqlData column names.
     *
     * @param array $apiData
     *
     * @return array
     */
    public static function jsonColsToSQLCols($apiData)
    {
        $sqlData['inversion'] = $apiData['@in-version'];
        $sqlData['recordid'] = $apiData['id'];
        $sqlData['evidence'] = $apiData['@evidence'];
        $sqlData['operation'] = $apiData['@operation'];
        $sqlData['externalids'] = addslashes(serialize(\array_key_exists(
            'external-ids',
            $apiData,
        ) ? $apiData['external-ids'] : []));

        return $sqlData;
    }

    /**
     * Save Json Data to SQL cache.
     *
     * @param array $changes
     *
     * @return int lastChangeID
     */
    public function saveWebhookData(array $changes): int
    {
        $urihelper = new \AbraFlexi\RO(null, ['offline' => true, 'url' => $this->url]);
        $source = new \Envms\FluentPDO\Literal("(SELECT id FROM changesapi WHERE serverurl LIKE '".$urihelper->url.'/c/'.$this->company."')");

        foreach ($changes as $apiData) {
            $sqlCols = self::jsonColsToSQLCols($apiData);

            $documentUri = $urihelper->url.'/c/'.$this->company.'/'.$sqlCols['evidence'].'/'.$sqlCols['recordid'];
            $context = $this->buildContext((int) $sqlCols['inversion'], (int) $sqlCols['recordid'], $sqlCols['evidence'], $urihelper->url.'/c/'.$this->company);

            try {
                $this->getFluentPDO()->insertInto('changes_cache')->values(array_merge(
                    ['source' => $source, 'target' => 'system'],
                    $sqlCols,
                    ['document_uri' => $documentUri, 'context' => $context],
                ))->execute();
            } catch (\Exception $exc) {
                $this->addStatusMessage($exc->getMessage().' Unknown server ?: '.$urihelper->url, 'warning');
            }
        }

        return isset($apiData) ? $this->saveLastProcessedVersion((int) $apiData['@in-version']) : 0;
    }

    /**
     * Build a JSON context blob from record_cache for the given change.
     *
     * Extracts adapter-specific fields (e.g. firma) from the most recent
     * cached document snapshot and returns them as a JSON string so the
     * generic eventor can expose them to env_mapping selectors without
     * knowing anything about AbraFlexi.
     *
     * Returns null when no matching record_cache row exists.
     */
    private function buildContext(int $inversion, int $recordId, string $evidence, string $serverUrl): ?string
    {
        try {
            $stmt = $this->getPdo()->prepare(
                'SELECT json FROM record_cache'
                .' WHERE inversion = :inv AND recordid = :rid AND evidence = :ev AND serverurl = :srv'
                .' LIMIT 1',
            );
            $stmt->execute([':inv' => $inversion, ':rid' => $recordId, ':ev' => $evidence, ':srv' => $serverUrl]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException) {
            return null;
        }

        if (empty($row['json'])) {
            return null;
        }

        $decoded = json_decode($row['json'], true);

        if (!\is_array($decoded)) {
            return null;
        }

        $context = [];

        // Normalise the firma relation object → plain customer code string
        $firma = $decoded['firma'] ?? null;
        $context['firma'] = match (true) {
            $firma === null => null,
            \is_array($firma) => ($firma['value'] ?? null) ?: null,
            default => (string) $firma ?: null,
        };

        // Filter out null values so env_mapping selectors get clean strings
        $context = array_filter($context, static fn ($v): bool => $v !== null);

        return $context ? json_encode($context, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES) : null;
    }

    /**
     * Empty given change version from cache.
     *
     * @param int $inVersion
     *
     * @return bool
     */
    public function wipeCacheRecord($inVersion)
    {
        return $this->fluent->deleteFrom('changes_cache')->where(
            'inversion',
            $inVersion,
        )->execute();
    }

    /**
     * @return bool
     */
    public function locked()
    {
        return false;
    }
}
