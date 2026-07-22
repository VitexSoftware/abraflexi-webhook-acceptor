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
 * Extends changes_cache with two generic columns that decouple the eventor
 * from AbraFlexi-specific knowledge:
 *
 * document_uri — a URI that uniquely identifies the source document across
 *   all adapter systems, e.g. the AbraFlexi native REST URL:
 *   https://server:5434/c/company/faktura-vydana/12345
 *   Downstream tools can use this URI to fetch cached data from the acceptor
 *   instead of re-querying the source system.
 *
 * context — a JSON object written by the adapter saver with any fields it
 *   wants to expose to event-rule env_mapping selectors. For AbraFlexi this
 *   includes e.g. {"firma": "AAA"}. The eventor merges these keys into the
 *   flat change row; their semantics are entirely the adapter's concern.
 */
final class ChangesCacheDocumentUri extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('changes_cache');
        $table
            ->addColumn('document_uri', 'string', [
                'limit' => 500,
                'null' => true,
                'default' => null,
                'comment' => 'Source-system URI identifying the changed document',
            ])
            ->addColumn('context', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Adapter-specific JSON context merged into env_mapping selectors by the eventor',
            ])
            ->addIndex(['document_uri'], ['name' => 'idx_changes_cache_document_uri'])
            ->update();
    }
}
