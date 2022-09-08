<?php

/**
 * AbraFlexi WebHook Acceptor  - Save changes into database
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2021-2022 Vitex Software
 */

namespace AbraFlexi\Acceptor\Saver;

/**
 * Description of PdoSQL
 *
 * @author vitex
 */
class PdoSQL extends \Ease\SQL\Engine implements AcceptorSaver {

    public $myTable = 'changes_cache';
    public $myKeyColumn = 'inversion';
    public $lastProcessedVersion = 0;
    public $company = '';
    public $url = '';
    private $serverUrl = '';

    /**
     * Prijmac WebHooku
     */
    public function __construct($options) {
        parent::__construct($options);
        $this->setupProperty($options, 'company', 'ABRAFLEXI_COMPANY');
        $this->setupProperty($options, 'url', 'ABRAFLEXI_URL');
    }

    /**
     * Keep current company code
     * 
     * @param string $companyCode
     */
    public function setCompany(string $companyCode) {
        $this->company = $companyCode;
    }

    /**
     * Keep current server url
     * 
     * @param string $url
     */
    public function setUrl(string $url) {
        $this->url = $url;
    }

    /**
     * Nacte posledni zpracovanou verzi
     *
     * @return int $version
     */
    public function getLastProcessedVersion() {
        $this->serverUrl = \Ease\Functions::cfg('ABRAFLEXI_URL') . '/c/' . $this->company;
        $lastProcessedVersion = null;
        $this->setmyTable('changesapi');
        $chRaw = $this->getColumnsFromSQL(['changeid'], ['serverurl' => $this->serverUrl]);
        if (isset($chRaw[0]['changeid'])) {
            $lastProcessedVersion = intval($chRaw[0]['changeid']);
        } else {
            $this->addStatusMessage(_("Last Processed Change ID Loading Failed"),
                    'warning');
        }
        $this->setmyTable('changes_cache');
        return $lastProcessedVersion;
    }

    /**
     * 
     * @param int $version
     * 
     * @return int Last loa
     */
    public function saveLastProcessedVersion(int $version) {
        $this->serverUrl = $this->url . '/c/' . $this->company;
        if ($version) {
            $this->lastProcessedVersion = $this->getLastProcessedVersion();
            $this->setmyTable('changesapi');
            $this->setKeyColumn('serverurl');
            $apich = ['changeid' => $version, 'serverurl' => $this->serverUrl];
            if ($this->lastProcessedVersion ? $this->updateToSQL($apich) : $this->insertToSQL($apich)) {
                $this->lastProcessedVersion = $version;
            } else {
                $this->addStatusMessage(_("Last Processed Change ID Saving Failed"), 'error');
            }
            $this->setmyTable('changes_cache');
            $this->setKeyColumn('inversion');
        }
        return $version;
    }

    /**
     * conver $sqlData column names to $jsonData column names
     * 
     * @param array $sqlData
     * 
     * @return array
     */
    public static function sqlColsToJsonCols($sqlData) {
        $jsonData['@in-version'] = $sqlData['inversion'];
        $jsonData['id'] = $sqlData['recordid'];
        $jsonData['@evidence'] = $sqlData['evidence'];
        $jsonData['@operation'] = $sqlData['operation'];
        $jsonData['external-ids'] = unserialize(stripslashes($sqlData['externalids']));
        return $jsonData;
    }

    /**
     * conver $jsonData column names to $sqlData column names
     * 
     * @param array $apiData
     * 
     * @return array
     */
    public static function jsonColsToSQLCols($apiData) {
        $sqlData['inversion'] = $apiData['@in-version'];
        $sqlData['recordid'] = $apiData['id'];
        $sqlData['evidence'] = $apiData['@evidence'];
        $sqlData['operation'] = $apiData['@operation'];
        $sqlData['externalids'] = addslashes(serialize(array_key_exists('external-ids',
                                $apiData) ? $apiData['external-ids'] : []));
        return $sqlData;
    }

    /**
     * Save Json Data to SQL cache
     * 
     * @param array $changes
     * 
     * @return int lastChangeID
     */
    public function saveWebhookData($changes) {
        $urihelper = new \AbraFlexi\RO(null, ['offline' => true]);
        $source = new \Envms\FluentPDO\Literal("(SELECT id FROM changesapi WHERE serverurl LIKE '" . $urihelper->url . '/c/' . $this->company . "')");
        foreach ($changes as $apiData) {
            $this->fluent->insertInto('changes_cache')->values(array_merge(['source' => $source, 'target' => 'system'], self::jsonColsToSQLCols($apiData)))->execute();
        }
        return isset($apiData) ? $this->saveLastProcessedVersion(intval($apiData['@in-version'])) : 0;
    }

    /**
     * Empty given change version from cache
     * 
     * @param int $inVersion
     * 
     * @return type
     */
    public function wipeCacheRecord($inVersion) {
        return $this->fluent->deleteFrom('changes_cache')->where('inversion',
                        $inVersion)->execute();
    }

    public function locked() {
        return false;
    }

}
