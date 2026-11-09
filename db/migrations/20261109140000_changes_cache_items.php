<?php

declare(strict_types=1);

/**
 * This file is part of the AbraFlexi Webhook Acceptor package
 *
 * https://github.com/VitexSoftware/abraflexi-webhook-acceptor
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Phinx\Migration\AbstractMigration;

/**
 * Document items on a change.
 *
 * items — JSON list of the document's items (id, cenik, nazev, mnozMj) attached to
 *   synthetic metastate changes of invoices, so an event rule can start one work
 *   item per invoice item (see MultiFlexi event_rule.item_filter / fanout).
 */
final class ChangesCacheItems extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('changes_cache');

        if (!$table->hasColumn('items')) {
            $table->addColumn('items', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON list of document items (id, cenik, nazev, mnozMj)',
            ])->update();
        }
    }
}
