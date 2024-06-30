<?php

/**
 * AbraFlexi - WebHook reciever
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2024 Vitex Software
 */

namespace AbraFlexi\Acceptor;

/**
 * Description of HookReciever
 *
 * @author vitex
 */
class HookReciever extends \AbraFlexi\Changes {

    /**
     *
     * @var array
     */
    public $handlerCache = [];

    /**
     * Current processed version
     *
     * @var int
     */
    public $globalVersion = null;

    /**
     * Posledni zpracovana verze
     * @var int
     */
    public $lastProcessedVersion = null;

    /**
     * Savers
     * @var array<\AbraFlexi\Acceptor\saver>
     */
    public $saver = [];

    /**
     * ChangesAPI handler
     * @var ChangesApi
     */
    private $sqlEngine;

    /**
     * Path to LockFile
     * @var string
     */
    private $lockfile;

    /**
     * Changes live here
     * @var array
     */
    public $changes = [];

    /**
     * WebHook Acceptor
     */
    public function __construct($properties = []) {
        parent::__construct(null, $properties);
        $this->sqlEngine = new ChangesApi();

        foreach (explode('|', \Ease\Shared::cfg('WHA_SAVER')) as $saverClass) {
            if ($saverClass) {
                $saverClass = '\\AbraFlexi\\Acceptor\\Saver\\' . $saverClass;
                if (class_exists($saverClass)) {
                    /** @var string $saverClass */
                    $this->saver[$saverClass] = new $saverClass($properties);
                } else {
                    $this->addStatusMessage('Saver Class ' . $saverClass . ' not found. Please check the configuration of WHA_SAVER', 'error');
                }
            } else {
                $this->addStatusMessage('Saver Class not specified. Please check the configuration of WHA_SAVER', 'error');
            }
        }
        if (empty($this->saver)) {
            $this->addStatusMessage('No Saver Class loaded. Please check the configuration of WHA_SAVER', 'error');
        }
    }

    /**
     * Takes input
     *
     * @param string $source filename to read
     *
     * @return string zaslaná data
     */
    public function listen($source = 'php://input') {
        $input = null;
        $this->url = $_SERVER['REQUEST_SCHEME'] . '://' . $this->remoteHost() . ':5434'; //TODO: Handle port somehow
        if ($this->debug) {
            $this->addStatusMessage(sprintf(_('Recieved Webhook from %s'), $this->url), 'debug');
        }
        $inputJSON = file_get_contents($source);
        if (strlen($inputJSON)) {
            $input = json_decode($inputJSON, true); //convert JSON into array
            $lastError = json_last_error();
            if ($lastError) {
                $this->addStatusMessage(json_last_error_msg(), 'warning');
            }
        }
        return $input;
    }

    /**
     * With Apache use HostnameLookus otherwise gethostbyaddr()
     * 
     * @return string
     */
    public function remoteHost() {
        if (array_key_exists('REMOTE_HOST', $_SERVER) === false) {
            $_SERVER['REMOTE_HOST'] = $_SERVER['REMOTE_ADDR'];
            switch ($_SERVER['SERVER_SOFTWARE']) {
                case 'Apache':
                    if ($this->debug) {
                        $this->addStatusMessage(_('Add HostnameLookups On to your Apache configuration'), 'warning');
                    }
                    break;
                case 'nginx':
                    $_SERVER['REMOTE_HOST'] = gethostbyaddr($_SERVER['REMOTE_ADDR']);
                    break;
                default:
                    if ($this->debug) {
                        $this->addStatusMessage(_('REMOTE_HOST is not set. Is HostnameLookups On ?'), 'warning');
                    }
            }
        }
        return $_SERVER['REMOTE_HOST'];
    }

    /**
     * Save changes into database or connector
     *
     * @param array $changes
     *
     * @return boolean All Savers successed
     */
    public function saveWebhookData(array $changes) {
        $results = [];
        foreach ($this->saver as $saver) {
            $saver->setCompany($this->company);
            $saver->setUrl($this->url);
            $results[] = $saver->saveWebhookData($changes);
        }
        return count($this->saver) && (count(array_filter($results)) == count($this->saver));
    }

    /**
     * Zpracuje změny
     *
     * @param array $changes
     *
     * @return array list IDS processed
     */
    function processAbraFlexiChanges(array $changes) {
        $changepos = 0;
        $doneIDd = [];
        foreach ($changes as $change) {
            $changepos++;
            $evidence = $change['@evidence'];
            $inVersion = intval($change['@in-version']);
            $operation = $change['@operation'];
            $id = intval($change['id']);
            $externalIDs = isset($change['external-ids']) ? $change['external-ids'] : [];

            if ($inVersion <= $this->lastProcessedVersion) {
                $this->addStatusMessage(sprintf(
                                _('Change version %s already processed'),
                                $inVersion
                        ), 'warning');
                continue;
            }
            $handlerClassName = \AbraFlexi\RO::evidenceToClassName($evidence);
            $handlerClassFile = 'System/whplugins/' . $handlerClassName . '.php';
            if (file_exists($handlerClassFile)) {
                include_once $handlerClassFile;
            }

            $handlerClass = '\\SpojeNet\\System\\whplugins\\' . $handlerClassName;
            if (class_exists($handlerClass)) {
                $saver = new $handlerClass(
                        $id,
                        ['evidence' => $evidence, 'operation' => $operation, 'external-ids' => $externalIDs,
                    'changeid' => $inVersion]
                );
                $saver->saveHistory();
                switch ($operation) {
                    case 'update':
                    case 'create':
                    case 'delete':
                        if ($saver->process($operation) && ($this->debug === true)) {
                            $this->addStatusMessage(
                                    $changepos . '/' . count($this->changes),
                                    'success'
                            );
                        }
                        break;
                    default:
                        $this->addStatusMessage(sprintf(_('Unknown operation %s'), $operation), 'error');
                        break;
                }
            } else {
                if ($this->debug === true) {
                    $this->addStatusMessage(sprintf(
                                    _('Handler Class %s does not exist'),
                                    addslashes($handlerClass)
                            ), 'warning');
                }
            }
            $this->saveLastProcessedVersion($inVersion);
        }
    }

    /**
     * Převezme změny
     *
     * @link https://www.abraflexi.eu/api/dokumentace/ref/changes-api/ Changes API
     * @param array $changes pole změn
     *
     * @return int Globální verze poslední změny
     */
    public function takeChanges($changes) {
        $result = null;
        if (!is_array($changes)) {
            \Ease\Shared::logger()->addToLog(
                    _('Empty WebHook request'),
                    'Warning'
            );
        } else {
            if (array_key_exists('winstrom', $changes)) {
                $this->globalVersion = intval($changes['winstrom']['@globalVersion']);
                $this->changes = $changes['winstrom']['changes'];
            }
            $result = is_numeric($changes['winstrom']['next']) ? $changes['winstrom']['next'] - 1 : $this->globalVersion;
        }
        return $result;
    }

    /**
     * Ulozi posledni zpracovanou verzi
     *
     * @param int $version
     */
    public function saveLastProcessedVersion($version) {
        $source = $this->url . '/c/' . $this->company;
        $this->lastProcessedVersion = $version;
        $this->myCreateColumn = null;
        $this->sqlEngine->deleteFromSQL(['serverurl' => $source]);
        if (is_null($this->sqlEngine->insertToSQL(['serverurl' => $source, 'changeid' => $version]))) {
            $this->sqlEngine->addStatusMessage(_("Last Processed Change ID Saving Failed"), 'error');
        } else {
            if ($this->debug === true) {
                $this->addStatusMessage(sprintf(_('Last Processed Change ID #%s Saved'), $version));
            }
        }
    }

    /**
     * Nacte posledni zpracovanou verzi
     *
     * @return int $version
     */
    public function getLastSavedVersion() {
        $lastProcessedVersion = null;

        foreach ($this->saver as $saver) {
            $saver->setCompany($this->company);
            $saver->setUrl($this->url);
            $lastProcessedVersion = $saver->getLastProcessedVersion();
        }
        if (is_null($lastProcessedVersion)) {
            $this->addStatusMessage(
                    _("Last Processed Change ID Loading Failed"),
                    'warning'
            );
            $lastProcessedVersion = 0;
        }
        return $lastProcessedVersion;
    }

    /**
     * Lock into lockfile
     *
     * @return int size of saved lockfile in bytes
     */
    public function lock() {
        return file_put_contents($this->lockfile, getmypid());
    }

    /**
     * Webhook processor lock check
     *
     * @returm locked by PID
     */
    public function locked() {
        return $this->isLocked() ? intval(file_get_contents($this->lockfile)) : 0;
    }

    /**
     *
     * @return boolean
     */
    public function isProcessRunning() {
        if (!file_exists($this->lockfile) || !is_file($this->lockfile)) {
            return false;
        }
        $pid = file_get_contents($this->lockfile);
        return posix_kill($pid, 0);
    }

    /**
     *
     * @return boolean
     */
    public function isLocked() {
        $locked = false;
        $lockfilePresent = file_exists($this->lockfile);
        if ($lockfilePresent) {
            if ($this->isProcessRunning()) {
                $locked = true;
            } else {
                $currentProcessPID = file_get_contents($this->lockfile);
                $locFileAge = time() - filemtime($this->lockfile);
                $this->addStatusMessage(sprintf(
                                'Ophraned lockfile found. pid: %d age: %s s.',
                                $currentProcessPID,
                                $locFileAge
                        ), 'error');
                $this->unlock();
            }
        }
        return $locked;
    }

    /**
     * Remove lockfile
     */
    public function unlock() {
        return file_exists($this->lockfile) ? unlink($this->lockfile) : true;
    }

    /**
     * skip webhooks with 'inversion' less or equal to $this->getLastProcessedVersion()
     *
     * @param array $webhooksRawData
     *
     * @return array
     */
    public function onlyFreshHooks($webhooksRawData) {
        $lastProcessed = $this->getLastSavedVersion();
        foreach ($webhooksRawData as $recId => $webhookRawData) {
            if ($webhookRawData['@in-version'] <= $lastProcessed) {
                unset($webhooksRawData[$recId]);
                $this->addStatusMessage(sprintf(_('Already proccessed change %d - skipping till %s'), $webhookRawData['@in-version'], $lastProcessed + 1), 'warning');
            }
        }
        return $webhooksRawData;
    }
}
