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

namespace AbraFlexi\Acceptor\Ui;

/**
 * Description of WebHookCacheView.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class WebHookCacheView extends \Ease\TWB4\Panel
{
    use \Ease\SQL\Orm;

    public function __construct()
    {
        $this->setMyTable('changes_cache');
        $changeTable = new \Ease\Html\TableTag(null, ['class' => 'table']);
        $changes = $this->getAllFromSQL();

        if (!empty($changes)) {
            foreach ($changes as $changeData) {
                if (\array_key_exists('created', $changeData)) {
                    $now = new \DateTime($changeData['created']);
                    $changeData['created'] = ' '.new \Ease\ui\LiveAge($now->getTimestamp());
                }

                $changeTable->addRowColumns($changeData);
            }
        }

        parent::__construct(
            _('Changes cached to process'),
            'info',
            $changeTable,
            sprintf(_('%d changes'), \count($changes)),
        );
    }
}
