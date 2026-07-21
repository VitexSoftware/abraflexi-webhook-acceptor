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
use AbraFlexi\Acceptor\MetaState\RecordCache;

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
            $this->createMock(RecordCache::class),
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

    public function testFullPaymentViaMatchingYieldsSettled(): void
    {
        // match_payment via banka does NOT set datUhrady; detect via zbyvaUhradit transition
        $previous = ['zbyvaUhradit' => '1000', 'datUhrady' => null];
        $current = ['zbyvaUhradit' => '0', 'datUhrady' => null];

        $this->assertSame('settled', $this->detect($previous, $current));
    }

    public function testFullPaymentWithDatUhradyAlsoYieldsSettled(): void
    {
        $previous = ['zbyvaUhradit' => '1000', 'datUhrady' => null];
        $current = ['zbyvaUhradit' => '0', 'datUhrady' => '2026-07-05'];

        $this->assertSame('settled', $this->detect($previous, $current));
    }

    public function testPartialPaymentYieldsNoMetaState(): void
    {
        $previous = ['zbyvaUhradit' => '1000', 'datUhrady' => null];
        $current = ['zbyvaUhradit' => '400', 'datUhrady' => null];

        $this->assertNull($this->detect($previous, $current));
    }

    public function testStornoTakesPriorityOverSettled(): void
    {
        $previous = ['storno' => null, 'zbyvaUhradit' => '1000', 'datUhrady' => null];
        $current = ['storno' => '1', 'zbyvaUhradit' => '0', 'datUhrady' => null];

        $this->assertSame('storno', $this->detect($previous, $current));
    }

    public function testReminder1(): void
    {
        $previous = ['datUp1' => null];
        $current = ['datUp1' => '2026-07-05'];

        $this->assertSame('remind1', $this->detect($previous, $current));
    }

    public function testReminder2(): void
    {
        $previous = ['datUp1' => '2026-06-01', 'datUp2' => null];
        $current = ['datUp1' => '2026-06-01', 'datUp2' => '2026-07-05'];

        $this->assertSame('remind2', $this->detect($previous, $current));
    }

    public function testReminder3(): void
    {
        $previous = ['datSmir' => null];
        $current = ['datSmir' => '2026-07-05'];

        $this->assertSame('remind3', $this->detect($previous, $current));
    }

    public function testPenalised(): void
    {
        $previous = ['datPenale' => null];
        $current = ['datPenale' => '2026-07-05'];

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
        $state = ['zbyvaUhradit' => '1000', 'datUhrady' => null, 'storno' => null, 'poznam' => ''];

        $this->assertNull($this->detect($state, $state));
    }
}
