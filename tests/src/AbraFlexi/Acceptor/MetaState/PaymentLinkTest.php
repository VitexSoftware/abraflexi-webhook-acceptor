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
 * Which links ("vazba") mean that an invoice was paid.
 */
class PaymentLinkTest extends \PHPUnit\Framework\TestCase
{
    public function testBankPaymentOfInvoice(): void
    {
        $link = ['a@ref' => '/c/vitex_software/faktura-vydana/2242.json', 'b@ref' => '/c/vitex_software/banka/7617.json'];
        $this->assertSame([2242], InvoiceMetaStateResolver::invoicesOfPaymentLink($link));
    }

    public function testEndsInEitherOrderAndWithoutSuffix(): void
    {
        $link = ['a@ref' => '/c/x/banka/7', 'b@ref' => '/c/x/faktura-vydana/12'];
        $this->assertSame([12], InvoiceMetaStateResolver::invoicesOfPaymentLink($link));
    }

    public function testRelationObjectIsAccepted(): void
    {
        $link = ['a' => (object) ['ref' => '/c/x/faktura-vydana/5'], 'b' => (object) ['ref' => '/c/x/pokladni-pohyb/9']];
        $this->assertSame([5], InvoiceMetaStateResolver::invoicesOfPaymentLink($link));
    }

    public function testDeductionOfAdvanceIsNoPayment(): void
    {
        // the advance linked to the item "Odpočet zálohy" of the tax document
        $link = ['a@ref' => '/c/x/faktura-vydana-polozka/719.json', 'b@ref' => '/c/x/faktura-vydana/76.json'];
        $this->assertSame([], InvoiceMetaStateResolver::invoicesOfPaymentLink($link));
    }

    public function testLinkBetweenInvoicesIsNoPayment(): void
    {
        $link = ['a@ref' => '/c/x/faktura-vydana/1.json', 'b@ref' => '/c/x/faktura-vydana/2.json'];
        $this->assertSame([], InvoiceMetaStateResolver::invoicesOfPaymentLink($link));
    }

    public function testEmptyLink(): void
    {
        $this->assertSame([], InvoiceMetaStateResolver::invoicesOfPaymentLink([]));
    }
}
