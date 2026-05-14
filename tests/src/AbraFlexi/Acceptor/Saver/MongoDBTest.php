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

namespace Test\AbraFlexi\Acceptor\Saver;

use AbraFlexi\Acceptor\Saver\MongoDB;

/**
 * @requires extension mongodb
 *
 * @covers \AbraFlexi\Acceptor\Saver\MongoDB
 */
class MongoDBTest extends \PHPUnit\Framework\TestCase
{
    protected MongoDB $object;

    protected function setUp(): void
    {
        $this->object = new MongoDB();
    }

    public function testSetCompany(): void
    {
        $this->object->setCompany('test_company');
        $this->assertInstanceOf(MongoDB::class, $this->object);
    }

    public function testSetUrl(): void
    {
        $this->object->setUrl('http://localhost:5434');
        $this->assertInstanceOf(MongoDB::class, $this->object);
    }

    public function testGetLastProcessedVersionReturnsNull(): void
    {
        $this->object->setUrl('http://test:5434');
        $this->object->setCompany('testcompany');
        $this->assertNull($this->object->getLastProcessedVersion());
    }

    public function testSaveAndGetLastProcessedVersion(): void
    {
        $this->object->setUrl('http://test:5434');
        $this->object->setCompany('testcompany');
        $this->object->saveLastProcessedVersion(456);
        $this->assertEquals(456, $this->object->getLastProcessedVersion());
    }

    public function testSaveWebhookData(): void
    {
        $this->object->setUrl('http://test:5434');
        $this->object->setCompany('testcompany');

        $changes = [
            [
                '@in-version' => 200,
                'id' => 10,
                '@evidence' => 'faktura-vydana',
                '@operation' => 'create',
                'external-ids' => ['ext:abc'],
            ],
            [
                '@in-version' => 201,
                'id' => 11,
                '@evidence' => 'adresar',
                '@operation' => 'update',
                'external-ids' => [],
            ],
        ];

        $result = $this->object->saveWebhookData($changes);
        $this->assertEquals(201, $result);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(\AbraFlexi\Acceptor\Saver\saver::class, $this->object);
    }
}
