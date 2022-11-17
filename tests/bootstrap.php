<?php

/**
 * AbraFlexi Changes processor - nastavení testů.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2020 Spoje.Net
 */
/**
 * Predefined server:One of:
 *
 * official|vitexsoftware|localhost
 */
include_once file_exists('../vendor/autoload.php') ? '../vendor/autoload.php' : 'vendor/autoload.php';

/**
 * Write logs as:
 */
if (!defined('EASE_APPNAME')) {
    define('EASE_APPNAME', 'AbraFlexiTest');
}
if (!defined('EASE_LOGGER')) {
    define('EASE_LOGGER', 'syslog');
}

$cfg = '../.env';
if (file_exists($cfg)) {
    \Ease\Shared::singleton()->loadConfig($cfg, true);
}


