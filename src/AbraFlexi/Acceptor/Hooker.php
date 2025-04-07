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

namespace AbraFlexi\Acceptor;

/**
 * Description of Hooker.
 *
 * @author vitex
 */
class Hooker extends \AbraFlexi\Hooks
{
    /**
     * WebHook url for Given ID of AbraFlexi instance.
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
            'webhook.php?instanceid='.$instanceId,
            $baseUrl,
        );

        return \Ease\Functions::addUrlParams($webHookUrl, ['company' => \Ease\Shared::cfg('ABRAFLEXI_COMPANY')]);
    }
}
