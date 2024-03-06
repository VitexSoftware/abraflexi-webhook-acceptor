<?php

/**
 * AbraFlexi WebHook Acceptor  - Push change to Kafka
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2021-2022 Vitex Software
 */

namespace AbraFlexi\Acceptor\Saver;

/**
 * Description of Kafka
 *
 * TODO
 *
 * @author vitex
 */
class Kafka implements saver
{
    /**
     *
     * @param type $param
     */
    public function save($param)
    {
        $conf = new \RdKafka\Conf();
        $conf->set('log_level', (string) LOG_DEBUG);
        $conf->set('debug', 'all');
        $rk = new \RdKafka\Producer($conf);
        $rk->addBrokers("10.0.0.1:9092,10.0.0.2:9092");
    }

    public function __destruct()
    {
    }

    public function setUrl(string $url)
    {
        ;
    }

    public function setCompany(string $companyCode)
    {
        ;
    }
}
