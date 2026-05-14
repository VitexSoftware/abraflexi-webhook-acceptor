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

use AbraFlexi\Acceptor\Saver\Kafka;

/**
 * @requires extension rdkafka
 *
 * @covers \AbraFlexi\Acceptor\Saver\Kafka
 */
class KafkaTest extends \PHPUnit\Framework\TestCase
{
    protected Kafka $object;

    protected function setUp(): void
    {
        $this->object = new Kafka();
    }

    public function testSetCompany(): void
    {
        $this->object->setCompany('test_company');
        $this->assertInstanceOf(Kafka::class, $this->object);
    }

    public function testSetUrl(): void
    {
        $this->object->setUrl('http://localhost:5434');
        $this->assertInstanceOf(Kafka::class, $this->object);
    }

    public function testGetLastProcessedVersion(): void
    {
        $this->assertNull($this->object->getLastProcessedVersion());
    }

    public function testSaveLastProcessedVersion(): void
    {
        $this->assertEquals(42, $this->object->saveLastProcessedVersion(42));
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(\AbraFlexi\Acceptor\Saver\saver::class, $this->object);
    }
}
