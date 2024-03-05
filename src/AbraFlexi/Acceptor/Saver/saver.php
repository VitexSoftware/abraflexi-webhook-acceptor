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
interface saver
{
    /**
     * Keep Current company
     */
    public function setCompany(string $companyCode);

    /**
     * Keep Current server url
     */
    public function setUrl(string $url);
}
