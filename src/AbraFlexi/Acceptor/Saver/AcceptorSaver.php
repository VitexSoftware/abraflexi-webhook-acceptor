<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AbraFlexi\Acceptor\Saver;

/**
 *
 * @author vitex
 */
interface AcceptorSaver {
    /**
     * Keep Current company
     */
    public function setCompany(string $companyCode);
}
