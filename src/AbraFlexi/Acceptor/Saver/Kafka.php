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
 * Description of Kafka.
 *
 * TODO
 *
 * @author vitex
 */
class Kafka implements saver
{
    public function __destruct()
    {
    }
    /**
     * @param type $param
     */
    public function save($param): void
    {
        $conf = new \RdKafka\Conf();
        $conf->set('log_level', (string) \LOG_DEBUG);
        $conf->set('debug', 'all');
        $rk = new \RdKafka\Producer($conf);
        $rk->addBrokers('10.0.0.1:9092,10.0.0.2:9092');
    }

    public function setUrl(string $url): void
    {
    }

    public function setCompany(string $companyCode): void
    {
    }
}
