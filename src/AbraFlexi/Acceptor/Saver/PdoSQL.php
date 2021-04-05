<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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

    /**
     * Prijmac WebHooku
     */
    public function __construct() {
        parent::__construct();
        $this->lastProcessedVersion = $this->getLastProcessedVersion();
    }

    /**
     * Nacte posledni zpracovanou verzi
     *
     * @return int $version
     */
    public function getLastProcessedVersion() {
        $lastProcessedVersion = null;
        $this->setmyTable('changesapi');
        $chRaw = $this->getColumnsFromSQL(['changeid'],
                ['serverurl' => \Ease\Functions::cfg('ABRAFLEXI_URL')]);
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
        if ($version) {
            $this->setmyTable('changesapi');
            $this->setKeyColumn('serverurl');
            $apich = ['changeid' => $version, 'serverurl' => \Ease\Functions::cfg('ABRAFLEXI_URL')];
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
     * @param array $sqlData
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
        foreach ($changes as $apiData) {
            $this->fluent->insertInto('changes_cache')->values(array_merge(['source' => $urihelper->url, 'target' => 'system'], self::jsonColsToSQLCols($apiData)))->execute();
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
