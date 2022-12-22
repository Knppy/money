<?php

namespace Knppy\Money\Tests\Unit;

use Knppy\Money\Currency;
use OutOfBoundsException;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertJson;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertTrue;

test('Unknown currency', function() {
   new Currency('unknown');
})->throws(OutOfBoundsException::class);

test('Comparison', function () {
    $c1 = new Currency('USD');
    $c2 = new Currency('TRY');

    assertTrue($c1->equals(new Currency('USD')));
    assertTrue($c2->equals(new Currency('TRY')));
    assertFalse($c1->equals($c2));
    assertFalse($c2->equals($c1));
});

test('Getters', function() {
    $c1 = new Currency('USD');

    assertEquals('US Dollar', $c1->getName());
    assertEquals('USD', $c1->getCurrency());
    assertEquals(840, $c1->getCode());
    assertEquals(1, $c1->getRate());
    assertEquals(2, $c1->getPrecision());
    assertEquals(100, $c1->getSubunit());
    assertEquals('$', $c1->getSymbol());
    assertEquals(true, $c1->isSymbolFirst());
    assertEquals('.', $c1->getDecimalMark());
    assertEquals(',', $c1->getThousandsSeparator());
    assertEquals('$', $c1->getPrefix());
    assertEquals('', $c1->getSuffix());
    assertNotEmpty($c1->toArray()['USD']);
    assertJson($c1->toJson());
    assertNotEmpty($c1->jsonSerialize()['USD']);

    $c2 = new Currency('CDF');
    assertEquals('CDF', $c2->getCurrency());
    assertEquals('Congolese Franc', $c2->getName());
    assertEquals(976, $c2->getCode());
    assertEquals(2, $c2->getPrecision());
    assertEquals(100, $c2->getSubunit());
    assertEquals('Fr', $c2->getSymbol());
    assertEquals(false, $c2->isSymbolFirst());
    assertEquals('.', $c2->getDecimalMark());
    assertEquals(',', $c2->getThousandsSeparator());
    assertEquals('', $c2->getPrefix());
    assertEquals(' Fr', $c2->getSuffix());
    assertNotEmpty($c2->toArray()['CDF']);
    assertJson($c2->toJson());
    assertNotEmpty($c2->jsonSerialize()['CDF']);
});