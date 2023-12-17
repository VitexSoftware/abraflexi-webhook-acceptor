<?php

/**
 * AbraFlexi WebHook Acceptor  - WebHook cache view widget
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2021-2022 Vitex Software
 */

namespace AbraFlexi\Acceptor\Ui;

/**
 * Description of WebHookCacheView
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class WebHookCacheView extends \Ease\TWB4\Panel
{
    use \Ease\SQL\Orm;

    public function __construct()
    {
        $this->takemyTable('changes_cache');
        $changeTable = new \Ease\Html\TableTag(null, ['class' => 'table']);
        $changes     = $this->getAllFromSQL();
        if (!empty($changes)) {
            foreach ($changes as $changeData) {
                if (array_key_exists('created', $changeData)) {
                    $now = new \DateTime($changeData['created']);
                    $changeData['created'] = ' ' . new \Ease\ui\LiveAge($now->getTimestamp());
                }
                $changeTable->addRowColumns($changeData);
            }
        }
        parent::__construct(
            _('Changes cached to process'),
            'info',
            $changeTable,
            sprintf(_('%d changes'), count($changes))
        );
    }
}
