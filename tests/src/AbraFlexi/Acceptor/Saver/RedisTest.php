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

use AbraFlexi\Acceptor\Saver\Redis;

/**
 * @requires extension redis
 *
 * @covers \AbraFlexi\Acceptor\Saver\Redis
 */
class RedisTest extends \PHPUnit\Framework\TestCase
{
    protected Redis $object;

    protected function setUp(): void
    {
        $this->object = new Redis();
    }

    public function testSetCompany(): void
    {
        $this->object->setCompany('test_company');
        $this->assertInstanceOf(Redis::class, $this->object);
    }

    public function testSetUrl(): void
    {
        $this->object->setUrl('http://localhost:5434');
        $this->assertInstanceOf(Redis::class, $this->object);
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
        $this->object->saveLastProcessedVersion(123);
        $this->assertEquals(123, $this->object->getLastProcessedVersion());
    }

    public function testSaveWebhookData(): void
    {
        $this->object->setUrl('http://test:5434');
        $this->object->setCompany('testcompany');

        $changes = [
            [
                '@in-version' => 100,
                'id' => 1,
                '@evidence' => 'adresar',
                '@operation' => 'create',
                'external-ids' => ['ext:123'],
            ],
            [
                '@in-version' => 101,
                'id' => 2,
                '@evidence' => 'adresar',
                '@operation' => 'update',
                'external-ids' => [],
            ],
        ];

        $result = $this->object->saveWebhookData($changes);
        $this->assertEquals(101, $result);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(\AbraFlexi\Acceptor\Saver\saver::class, $this->object);
    }
}
