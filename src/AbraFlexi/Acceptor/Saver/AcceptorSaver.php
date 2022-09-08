<?php

/**
 * AbraFlexi WebHook Acceptor  - AcceptorSaver interface
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2021-2022 Vitex Software
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
    /**
     * Keep Current server url
     */
    public function setUrl(string $url);
}
