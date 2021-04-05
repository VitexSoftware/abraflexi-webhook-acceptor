<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace SpojeNet\System\ui;
/**
 * Description of WebHookCacheView
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class WebHookCacheView extends \Ease\TWB\Panel
{

    use \Ease\SQL\Orm;

    public function __construct()
    {
        $this->takemyTable('changes_cache');
        $changeTable = new \Ease\Html\TableTag(null, ['class' => 'table']);
        $changes     = $this->getAllFromSQL();
        if (!empty($changes)) {
            foreach ($changes as $changeData) {
                if(array_key_exists('created',$changeData)){
                    $now = new \DateTime($changeData['created']);
                    $changeData['created'] = ' '. new \Ease\ui\LiveAge( $now->getTimestamp() );
                }
                $changeTable->addRowColumns($changeData);
            }
        }
        parent::__construct(_('Changes cached to process'),'info', $changeTable,
            sprintf(_('%d changes'), count($changes)));
    }
}
