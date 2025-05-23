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

namespace Test\AbraFlexi\Acceptor;

use AbraFlexi\Acceptor\HookReciever;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2022-06-21 at 09:52:50.
 */
class HookRecieverTest extends \PHPUnit\Framework\TestCase
{
    protected HookReciever $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new HookReciever(\Ease\Shared::instanced()->configuration);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    /**
     * @covers \AbraFlexi\Acceptor\HookReciever::listen
     */
    public function testlisten(): void
    {
        $this->assertIsArray($this->object->listen(__DIR__.'/../../../hooks/webhook-1615591760.json'));
    }

    /**
     * @covers \AbraFlexi\Acceptor\HookReciever::saveWebhookData
     */
    public function testsaveWebhookData(): void
    {
        $this->assertTrue($this->object->saveWebhookData(json_decode(file_get_contents(__DIR__.'/../../../hooks/webhook-1615591760.json'), true)), 'webhook data not saved');
    }

    /**
     * @covers \AbraFlexi\Acceptor\HookReciever::processAbraFlexiChanges
     *
     * @todo   Implement testprocessAbraFlexiChanges().
     */
    public function testprocessAbraFlexiChanges(): void
    {
        $this->assertEquals('', $this->object->processAbraFlexiChanges());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers \AbraFlexi\Acceptor\HookReciever::takeChanges
     *
     * @todo   Implement testtakeChanges().
     */
    public function testtakeChanges(): void
    {
        $this->assertEquals('', $this->object->takeChanges());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers \AbraFlexi\Acceptor\HookReciever::saveLastProcessedVersion
     *
     * @todo   Implement testsaveLastProcessedVersion().
     */
    public function testsaveLastProcessedVersion(): void
    {
        $this->assertEquals('', $this->object->saveLastProcessedVersion());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers \AbraFlexi\Acceptor\HookReciever::getLastSavedVersion
     *
     * @todo   Implement testgetLastSavedVersion().
     */
    public function testgetLastSavedVersion(): void
    {
        $this->assertEquals('', $this->object->getLastSavedVersion());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers \AbraFlexi\Acceptor\HookReciever::lock
     *
     * @todo   Implement testlock().
     */
    public function testlock(): void
    {
        $this->assertEquals('', $this->object->lock());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers \AbraFlexi\Acceptor\HookReciever::locked
     *
     * @todo   Implement testlocked().
     */
    public function testlocked(): void
    {
        $this->assertEquals('', $this->object->locked());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers \AbraFlexi\Acceptor\HookReciever::isProcessRunning
     *
     * @todo   Implement testisProcessRunning().
     */
    public function testisProcessRunning(): void
    {
        $this->assertEquals('', $this->object->isProcessRunning());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers \AbraFlexi\Acceptor\HookReciever::isLocked
     *
     * @todo   Implement testisLocked().
     */
    public function testisLocked(): void
    {
        $this->assertEquals('', $this->object->isLocked());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers \AbraFlexi\Acceptor\HookReciever::unlock
     *
     * @todo   Implement testunlock().
     */
    public function testunlock(): void
    {
        $this->assertEquals('', $this->object->unlock());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers \AbraFlexi\Acceptor\HookReciever::onlyFreshHooks
     *
     * @todo   Implement testonlyFreshHooks().
     */
    public function testonlyFreshHooks(): void
    {
        $this->assertEquals('', $this->object->onlyFreshHooks());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
