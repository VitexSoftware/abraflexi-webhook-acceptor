<?php

use Phinx\Migration\AbstractMigration;

class ChangesCache extends AbstractMigration {

    public function change() {
        $table = $this->table('changes_cache',
                ['id' => false, 'primary_key' => ['inversion']]);

        $table
                ->addColumn('inversion', 'integer', ['signed' => false])
                ->addColumn('recordid', 'integer', ['identity' => false, 'signed' => false])
                ->addColumn('evidence', 'string', ['limit' => 60])
                ->addColumn('operation', $this->getAdapter()->getOption('adapter') == 'sqlite' ? 'string' : 'enum', ['values' => ['create', 'update', 'delete']])
                ->addColumn('externalids', 'string', ['limit' => 300])
                ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('source', 'string', ['comment' => 'Source System ID', 'limit' => 255])
                ->addColumn('target', 'string', ['comment' => 'Source System ID', 'limit' => 30])
                ->create();
    }

}
