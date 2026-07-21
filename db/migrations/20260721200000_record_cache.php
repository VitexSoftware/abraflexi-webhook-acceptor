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
 * Replaces the narrow invoice_state_cache table with a generic record_cache.
 *
 * Every incoming webhook that triggers a document download produces one new
 * row here. Rows for the same document (recordid + evidence + serverurl) are
 * distinguished by the AbraFlexi change version number (inversion) that
 * caused the download, which corresponds to changes_cache.inversion.
 *
 * Querying by (recordid, evidence, serverurl) ORDER BY inversion DESC returns
 * all retained versions newest-first, so:
 *   LIMIT 1          → current state
 *   LIMIT 1 OFFSET 1 → previous state
 *   no LIMIT         → full retained history
 *
 * Storing documents as JSON keeps the schema evidence-agnostic, preserves
 * every field (firma, kod, …) for downstream consumers, and allows FULLTEXT
 * search over the raw JSON without prior deserialisation.
 *
 * Retention policy (deleting old versions) can be applied as a separate
 * maintenance step; the minimum for metastate detection is two versions.
 */
final class RecordCache extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('invoice_state_cache')) {
            $this->table('invoice_state_cache')->drop()->save();
        }

        $table = $this->table('record_cache', ['id' => true, 'primary_key' => 'id']);

        $table
            ->addColumn('inversion', 'integer', ['signed' => false, 'comment' => 'AbraFlexi change version — ref to changes_cache.inversion'])
            ->addColumn('recordid', 'integer', ['signed' => false])
            ->addColumn('evidence', 'string', ['limit' => 60])
            ->addColumn('serverurl', 'string', ['limit' => 300])
            ->addColumn('json', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG])
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['inversion', 'recordid', 'evidence', 'serverurl'], ['unique' => true, 'name' => 'uk_record_version'])
            ->addIndex(['recordid', 'evidence', 'serverurl'], ['name' => 'idx_document'])
            ->addIndex(['json'], ['type' => 'fulltext', 'name' => 'ft_json'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('record_cache')) {
            $this->table('record_cache')->drop()->save();
        }
    }
}
