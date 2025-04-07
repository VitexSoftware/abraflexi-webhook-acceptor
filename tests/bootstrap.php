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

/**
 * Predefined server:One of:
 *
 * official|vitexsoftware|localhost
 */
include_once file_exists('../vendor/autoload.php') ? '../vendor/autoload.php' : 'vendor/autoload.php';

/**
 * Write logs as:
 */
if (!\defined('EASE_APPNAME')) {
    \define('EASE_APPNAME', 'AbraFlexiTest');
}

if (!\defined('EASE_LOGGER')) {
    \define('EASE_LOGGER', 'syslog');
}

$cfg = '../.env';

if (file_exists($cfg)) {
    \Ease\Shared::singleton()->loadConfig($cfg, true);
}
