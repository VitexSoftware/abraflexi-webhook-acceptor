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

class ChangesCache extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table(
            'changes_cache',
            ['id' => false, 'primary_key' => ['inversion']],
        );

        $table
            ->addColumn('inversion', 'integer', ['signed' => false])
            ->addColumn('recordid', 'integer', ['identity' => false, 'signed' => false])
            ->addColumn('evidence', 'string', ['limit' => 60])
            ->addColumn('operation', $this->getAdapter()->getOption('adapter') === 'sqlite' ? 'string' : 'enum', ['values' => ['create', 'update', 'delete']])
            ->addColumn('externalids', 'string', ['limit' => 300])
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('source', 'integer', ['comment' => 'Source System ID', 'signed' => false, 'limit' => 255])
            ->addColumn('target', 'string', ['comment' => 'Source System ID', 'limit' => 30])
            ->create();
    }
}
