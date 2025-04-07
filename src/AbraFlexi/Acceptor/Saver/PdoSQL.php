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
    public $myTable = 'changes_cache';
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
     * @return int $version
     */
    public function getLastProcessedVersion()
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
     * @return int Last loa
     */
    public function saveLastProcessedVersion(int $version)
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
    public function saveWebhookData($changes)
    {
        $urihelper = new \AbraFlexi\RO(null, ['offline' => true, 'url' => $this->url]);
        $source = new \Envms\FluentPDO\Literal("(SELECT id FROM changesapi WHERE serverurl LIKE '".$urihelper->url.'/c/'.$this->company."')");

        foreach ($changes as $apiData) {
            try {
                $this->getFluentPDO()->insertInto('changes_cache')->values(array_merge(['source' => $source, 'target' => 'system'], self::jsonColsToSQLCols($apiData)))->execute();
            } catch (\Exception $exc) {
                $this->addStatusMessage($exc->getMessage().' Unknown server ?: '.$urihelper->url, 'warning');
            }
        }

        return isset($apiData) ? $this->saveLastProcessedVersion((int) $apiData['@in-version']) : 0;
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
