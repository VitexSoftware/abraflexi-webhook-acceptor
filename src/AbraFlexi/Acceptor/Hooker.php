<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AbraFlexi\Acceptor;

/**
 * Description of Hooker
 *
 * @author vitex
 */
class Hooker extends \AbraFlexi\Hooks {

    /**
     * WebHook url for Given ID of AbraFlexi instance
     * 
     * @param int $instanceId
     * 
     * @return string URL for WebHook
     */
    public static function webHookUrl($instanceId) {
        $baseUrl = \Ease\Document::phpSelf();
        $urlInfo = parse_url($baseUrl);
        $curFile = basename($urlInfo['path']);
        $webHookUrl = str_replace($curFile,
                'webhook.php?instanceid=' . $instanceId, $baseUrl);
        return $webHookUrl;
    }

}
