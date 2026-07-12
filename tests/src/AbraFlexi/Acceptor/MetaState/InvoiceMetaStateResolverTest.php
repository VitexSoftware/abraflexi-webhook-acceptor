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

namespace Test\AbraFlexi\Acceptor\MetaState;

use AbraFlexi\Acceptor\MetaState\InvoiceMetaStateResolver;

/**
 * Exercises the previous-vs-current diff logic in isolation (no AbraFlexi
 * API / DB access), via reflection on the private detectMetaState() method.
 */
class InvoiceMetaStateResolverTest extends \PHPUnit\Framework\TestCase
{
    private InvoiceMetaStateResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new InvoiceMetaStateResolver(
            $this->createMock(\AbraFlexi\Acceptor\MetaState\InvoiceStateCache::class),
        );
    }

    private function detect(array $previous, array $current): ?string
    {
        $method = new \ReflectionMethod(InvoiceMetaStateResolver::class, 'detectMetaState');
        $method->setAccessible(true);

        return $method->invoke($this->resolver, $previous, $current);
    }

    public function testSupportsOnlyInvoiceUpdate(): void
    {
        $this->assertTrue($this->resolver->supports('faktura-vydana', 'update'));
        $this->assertFalse($this->resolver->supports('faktura-vydana', 'create'));
        $this->assertFalse($this->resolver->supports('adresar', 'update'));
    }

    public function testFullPaymentYieldsSettled(): void
    {
        $previous = ['zbyvauhradit' => '1000', 'datuhrady' => null];
        $current = ['zbyvauhradit' => '0', 'datuhrady' => '2026-07-05'];

        $this->assertSame('settled', $this->detect($previous, $current));
    }

    public function testPartialPaymentWithoutDatUhradyYieldsNoMetaState(): void
    {
        $previous = ['zbyvauhradit' => '1000', 'datuhrady' => null];
        $current = ['zbyvauhradit' => '400', 'datuhrady' => null];

        $this->assertNull($this->detect($previous, $current));
    }

    public function testStornoTakesPriorityOverSettled(): void
    {
        $previous = ['storno' => null, 'zbyvauhradit' => '1000', 'datuhrady' => null];
        $current = ['storno' => '1', 'zbyvauhradit' => '0', 'datuhrady' => '2026-07-05'];

        $this->assertSame('storno', $this->detect($previous, $current));
    }

    public function testReminder1(): void
    {
        $previous = ['datup1' => null];
        $current = ['datup1' => '2026-07-05'];

        $this->assertSame('remind1', $this->detect($previous, $current));
    }

    public function testReminder2(): void
    {
        $previous = ['datup1' => '2026-06-01', 'datup2' => null];
        $current = ['datup1' => '2026-06-01', 'datup2' => '2026-07-05'];

        $this->assertSame('remind2', $this->detect($previous, $current));
    }

    public function testReminder3(): void
    {
        $previous = ['datsmir' => null];
        $current = ['datsmir' => '2026-07-05'];

        $this->assertSame('remind3', $this->detect($previous, $current));
    }

    public function testPenalised(): void
    {
        $previous = ['datpenale' => null];
        $current = ['datpenale' => '2026-07-05'];

        $this->assertSame('penalised', $this->detect($previous, $current));
    }

    public function testNewInventoryNoteYieldsInventory(): void
    {
        $previous = ['poznam' => "Some older note\nInventarizace:2026-06-01"];
        $current = ['poznam' => "Some older note\nInventarizace:2026-06-01\nInventarizace:2026-07-05"];

        $this->assertSame('inventory', $this->detect($previous, $current));
    }

    public function testUnchangedInventoryNoteYieldsNoMetaState(): void
    {
        $previous = ['poznam' => 'Inventarizace:2026-06-01'];
        $current = ['poznam' => 'Inventarizace:2026-06-01'];

        $this->assertNull($this->detect($previous, $current));
    }

    public function testNoRelevantChangeYieldsNull(): void
    {
        $state = ['zbyvauhradit' => '1000', 'datuhrady' => null, 'storno' => null, 'poznam' => ''];

        $this->assertNull($this->detect($state, $state));
    }
}
