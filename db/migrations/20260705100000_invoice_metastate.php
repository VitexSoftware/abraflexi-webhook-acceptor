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

use Phinx\Migration\AbstractMigration;

/**
 * Widens changes_cache.operation beyond create/update/delete so
 * InvoiceMetaStateResolver can append post-hoc metastate rows (settled,
 * remind1/2/3, penalised, storno, inventory), and introduces a surrogate
 * autoincrement key so several rows can legitimately share the same AbraFlexi
 * `inversion` (the real update plus any metastate rows derived from it)
 * without colliding with AbraFlexi's own ever-increasing version numbers.
 *
 * `inversion` remains the AbraFlexi changeset version (kept as a plain
 * indexed column, no longer the primary key). Consumers polling by
 * `inversion > :last` are unaffected; only ordering ties on identical
 * `inversion` values are now broken by insertion order via `id`.
 */
final class InvoiceMetastate extends AbstractMigration
{
    public function change(): void
    {
        $adapter = $this->getAdapter()->getOption('adapter');

        if ('mysql' === $adapter) {
            // MySQL requires DROP PRIMARY KEY and the new AUTO_INCREMENT
            // column to become PRIMARY KEY within the very same ALTER TABLE
            // statement (error 1075 otherwise), which the Phinx Table
            // fluent API cannot express reliably - so raw SQL here.
            $this->execute(
                'ALTER TABLE changes_cache '
                .'MODIFY operation VARCHAR(30) NULL, '
                .'DROP PRIMARY KEY, '
                .'ADD id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST, '
                .'ADD INDEX idx_changes_cache_inversion (inversion)',
            );
        } else {
            $table = $this->table('changes_cache');
            $table
                ->changeColumn('operation', 'string', ['limit' => 30])
                ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
                ->changePrimaryKey(['id'])
                ->update();
            $table
                ->addIndex(['inversion'])
                ->update();
        }

        $invoiceState = $this->table(
            'invoice_state_cache',
            ['id' => false, 'primary_key' => ['recordid', 'evidence', 'serverurl']],
        );

        $invoiceState
            ->addColumn('recordid', 'integer', ['signed' => false])
            ->addColumn('evidence', 'string', ['limit' => 60])
            ->addColumn('serverurl', 'string', ['limit' => 300])
            ->addColumn('zbyvauhradit', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('datuhrady', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('datup1', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('datup2', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('datsmir', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('datpenale', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('storno', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('poznam', 'text', ['null' => true])
            ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->create();
    }
}
