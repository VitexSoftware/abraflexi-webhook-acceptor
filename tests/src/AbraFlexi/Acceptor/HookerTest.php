<?php

namespace Test\AbraFlexi\Acceptor;

use AbraFlexi\Acceptor\Hooker;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2022-06-21 at 09:52:49.
 */
class HookerTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var Hooker
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void {
        $this->object = new Hooker();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void {
        
    }

    /**
     * @covers AbraFlexi\Acceptor\Hooker::webHookUrl
     * @todo   Implement testwebHookUrl().
     */
    public function testwebHookUrl() {
        $this->assertEquals('', $this->object->webHookUrl());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

}
