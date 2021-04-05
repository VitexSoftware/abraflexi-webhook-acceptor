<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AbraFlexi\Acceptor\Saver;

/**
 * Description of Kafka
 *
 * @author vitex
 */
class Kafka implements AcceptorSaver {

    public function save($param) {
        $conf = new RdKafka\Conf();
        $conf->set('log_level', (string) LOG_DEBUG);
        $conf->set('debug', 'all');
        $rk = new RdKafka\Producer($conf);
        $rk->addBrokers("10.0.0.1:9092,10.0.0.2:9092");
    }

    
    public function __destruct() {
        
    }
    
}
