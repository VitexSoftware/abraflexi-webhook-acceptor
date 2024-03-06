<?php

/**
 * AbraFlexi WebHook Acceptor  - Endpoint Handler
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2021-2022 Vitex Software
 */

namespace AbraFlexi\Acceptor;

/**
 * Description of Hooker
 *
 * @author vitex
 */
class Hooker extends \AbraFlexi\Hooks
{
    /**
     * WebHook url for Given ID of AbraFlexi instance
     *
     * @param int $instanceId
     *
     * @return string URL for WebHook
     */
    public static function webHookUrl($instanceId)
    {
        $baseUrl = \Ease\Document::phpSelf();
        $urlInfo = parse_url($baseUrl);
        $curFile = basename($urlInfo['path']);

        $webHookUrl = str_replace(
            $curFile,
            'webhook.php?instanceid=' . $instanceId,
            $baseUrl
        );
        return \Ease\Functions::addUrlParams($webHookUrl, ['company' => \Ease\Shared::cfg('ABRAFLEXI_COMPANY')]);
    }
}
