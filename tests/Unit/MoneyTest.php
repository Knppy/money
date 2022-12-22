<?php
/** @noinspection PhpComposerExtensionStubsInspection */

use Knppy\Money\Currency;
use Knppy\Money\Exceptions\UnexpectedAmountException;
use Knppy\Money\Money;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertJson;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertNotEquals;
use function PHPUnit\Framework\assertNotSame;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

test('Addition', function () {
    $m1 = new Money(1100.101, new Currency('USD'));
    $m2 = new Money(1100.021, new Currency('USD'));
    $sum = $m1->add($m2);

    assertEquals(new Money(2200.12, new Currency('USD')), $sum);
    assertNotEquals($sum, $m1);
    assertNotEquals($sum, $m2);
});

test('Addition mutables', function () {
    $money1 = (new Money(1100.101, new Currency('USD')))->mutable();
    $money2 = new Money(1100.021, new Currency('USD'));

    $money1->add($money2);

    assertEquals((new Money(2200.12, new Currency('USD')))->mutable(), $money1);
    assertNotEquals((new Money(2200.12, new Currency('USD')))->mutable(), $money2);
});

test('Addition different currencies', function () {
    $m1 = new Money(100, new Currency('USD'));
    $m2 = new Money(100, new Currency('TRY'));

    $m1->add($m2);
})->expectException(InvalidArgumentException::class);

test('Allocate', function () {
    $m1 = new Money(100, new Currency('USD'));

    [$part1, $part2, $part3] = $m1->allocate([1, 1, 1]);
    assertEquals(new Money(34, new Currency('USD')), $part1);
    assertEquals(new Money(33, new Currency('USD')), $part2);
    assertEquals(new Money(33, new Currency('USD')), $part3);

    $m2 = new Money(101, new Currency('USD'));

    [$part1, $part2, $part3] = $m2->allocate([1, 1, 1]);
    assertEquals(new Money(34, new Currency('USD')), $part1);
    assertEquals(new Money(34, new Currency('USD')), $part2);
    assertEquals(new Money(33, new Currency('USD')), $part3);
});

test('Allocate where order is important', function () {
    $m = new Money(5, new Currency('USD'));

    [$part1, $part2] = $m->allocate([3, 7]);
    assertEquals(new Money(2, new Currency('USD')), $part1);
    assertEquals(new Money(3, new Currency('USD')), $part2);

    [$part1, $part2] = $m->allocate([7, 3]);
    assertEquals(new Money(4, new Currency('USD')), $part1);
    assertEquals(new Money(1, new Currency('USD')), $part2);
});

test('Callback format locale', function() {
    $m = new Money(1, new Currency('USD'));

    $actual = $m->formatLocale(null, function (NumberFormatter $formatter) {
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);
    });

    $formatter = new NumberFormatter($m::getLocale(), NumberFormatter::CURRENCY);
    $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);
    $expected = $formatter->formatCurrency('0.01', 'USD');

    assertEquals($expected, $actual);
});

test('Comparison', function () {
    $m1 = new Money(50, new Currency('USD'));
    $m2 = new Money(100, new Currency('USD'));
    $m3 = new Money(200, new Currency('USD'));

    assertEquals(-1, $m2->compare($m3));
    assertEquals(1, $m2->compare($m1));
    assertEquals(0, $m2->compare($m2));

    assertTrue($m2->equals($m2));
    assertFalse($m3->equals($m2));

    assertTrue($m3->greaterThan($m2));
    assertFalse($m2->greaterThan($m3));

    assertTrue($m2->greaterThanOrEqual($m2));
    assertFalse($m2->greaterThanOrEqual($m3));

    assertTrue($m2->lessThan($m3));
    assertFalse($m3->lessThan($m2));

    assertTrue($m2->lessThanOrEqual($m2));
    assertFalse($m3->lessThanOrEqual($m2));
});

test('Comparison with different currencies', function () {
    $m1 = new Money(100, new Currency('USD'));
    $m2 = new Money(100, new Currency('TRY'));

    $m1->compare($m2);
})->expectException(InvalidArgumentException::class);

test('Comparators', function () {
    $m1 = new Money(0, new Currency('USD'));
    $m2 = new Money(-1, new Currency('USD'));
    $m3 = new Money(1, new Currency('USD'));
    $m4 = new Money(1, new Currency('USD'));
    $m5 = new Money(1, new Currency('USD'));
    $m6 = new Money(-1, new Currency('USD'));

    assertTrue($m1->isZero());
    assertTrue($m2->isNegative());
    assertTrue($m3->isPositive());
    assertFalse($m4->isZero());
    assertFalse($m5->isNegative());
    assertFalse($m6->isPositive());
});

test('Conversion', function () {
    $m1 = new Money(100, new Currency('USD'));
    $m2 = new Money(350, new Currency('TRY'));

    assertEquals($m1->convert(new Currency('TRY'), 3.5), $m2);
});

test('Convert unit', function () {
    $m1 = new Money(100, new Currency('USD'), true);
    $m2 = new Money(100, new Currency('USD'));

    assertEquals(10000, $m1->getAmount());
    assertNotEquals($m1, $m2);
});

test('Division', function () {
    $m1 = new Money(2, new Currency('USD'));
    $m2 = new Money(10, new Currency('USD'));

    assertEquals($m1, $m2->divide(5));
    assertNotEquals($m1, $m2->divide(2));
});

test('Division Mutable', function () {
    $money1 = new Money(2, new Currency('USD'));
    $money2 = (new Money(10, new Currency('USD')))->mutable();

    $money2->divide(5);

    assertEquals((new Money(2, new Currency('USD')))->mutable(), $money2);
    assertTrue($money2->equals($money1));
});

test('Division invalid', function () {
    $m = new Money(100, new Currency('USD'));

    $m->divide(0);
})->expectException(InvalidArgumentException::class);

test('Format', function ($expected, $cur, $amount, $message) {
    assertEquals($expected, (string)new Money($amount, new Currency($cur)), $message);
})->with([
    ['₺1.548,48', 'TRY', 154848.25895, 'Example: ' . __LINE__],
    ['$1,548.48', 'USD', 154848.25895, 'Example: ' . __LINE__],
]);

test('Format for humans', function ($expected, $cur, $amount, $locale, $message) {
    assertEquals($expected, (new Money($amount, new Currency($cur)))->formatForHumans($locale), $message);
})->with([
    ['€1,55K', 'EUR', 154848.25895, 'nl_NL', 'Example: ' . __LINE__],
    ['$1.55K', 'USD', 154848.25895, 'en_US', 'Example: ' . __LINE__],
]);

test('Format locale', function ($expected, $cur, $amount, $locale, $message) {
    assertEquals($expected, (new Money($amount, new Currency($cur)))->formatLocale($locale), $message);
})->with([
    ['₺1.548,48', 'TRY', 154848.25895, 'tr_TR', 'Example: ' . __LINE__],
    ['$1,548.48', 'USD', 154848.25895, 'en_US', 'Example: ' . __LINE__],
]);

test('Format simple', function () {
    $m1 = new Money(1, new Currency('USD'));
    $m2 = new Money(10, new Currency('USD'));
    $m3 = new Money(100, new Currency('USD'));
    $m4 = new Money(1000, new Currency('USD'));
    $m5 = new Money(10000, new Currency('USD'));
    $m6 = new Money(100000, new Currency('USD'));

    assertEquals('0.01', $m1->formatSimple());
    assertEquals('0.10', $m2->formatSimple());
    assertEquals('1.00', $m3->formatSimple());
    assertEquals('10.00', $m4->formatSimple());
    assertEquals('100.00', $m5->formatSimple());
    assertEquals('1,000.00', $m6->formatSimple());
});

test('Format without zeros', function () {
    $m1 = new Money(100, new Currency('USD'), true);
    $m2 = new Money(100.50, new Currency('USD'), true);

    assertEquals('$100.00', $m1->format());
    assertEquals('$100', $m1->formatWithoutZeroes());

    assertEquals('$100.50', $m2->format());
    assertEquals('$100.50', $m2->formatWithoutZeroes());
});


test('Getters', function () {
    $m = new Money(100, new Currency('USD'));

    assertEquals(100, $m->getAmount());
    assertEquals(1, $m->getValue());
    assertEquals(new Currency('USD'), $m->getCurrency());
    assertNotEmpty($m->toArray());
    assertJson($m->toJson());
    assertNotEmpty($m->jsonSerialize());
});

test('Locale', function () {
    Money::locale(null);
    assertEquals('en_GB', Money::getLocale());

    Money::locale('en_US');
    assertEquals('en_US', Money::getLocale());
});

test('Making mutable', function() {
    $money = (new Money(1000, new Currency('USD')))->immutable();

    $this->assertTrue(!$money->isMutable());
    $this->assertFalse(!$money->mutable()->isMutable());
});

test('Multiply', function () {
    $m1 = new Money(15, new Currency('USD'));
    $m2 = new Money(1, new Currency('USD'));

    assertEquals($m1, $m2->multiply(15));
    assertNotEquals($m1, $m2->multiply(10));
});

test('Multiple Mutable', function () {
    $money1 = new Money(15, new Currency('USD'));
    $money2 = (new Money(1, new Currency('USD')))->mutable();

    $money2->multiply(15);

    assertEquals((new Money(15, new Currency('USD')))->mutable(), $money2);
    assertTrue($money2->equals($money1));
});

test('Parsing amount from money', function () {
    $money1 = new Money(1000, new Currency('USD'));
    $money2 = new Money($money1, new Currency('USD'));

    assertEquals($money1, $money2);
});

test('Parsing big amount', function () {
    assertEquals((string)new Money(123456789.321, new Currency('USD'), true), '$123,456,789.32');
});

test('Parsing string amount', function () {
    assertEquals(new Money('1', new Currency('USD')), new Money(1, new Currency('USD')));
    assertEquals(new Money('1.1', new Currency('USD')), new Money(1.1, new Currency('USD')));
});

test('Parsing string with exception', function () {
    new Money('foo', new Currency('USD'));
})->expectException(UnexpectedAmountException::class);

test('Rounded amount', function () {
    $money = new Money(1000.213, new Currency('USD'));

    assertSame(1000.21, $money->getAmountRounded());
});

test('Rounded amount with invalid mode', function () {
    $money = new Money(1000.213, new Currency('USD'));

    $money->round(2, 5);
})->expectException(OutOfBoundsException::class);

test('Same currency', function () {
    $m = new Money(100, new Currency('USD'));

    assertTrue($m->isSameCurrency(new Money(100, new Currency('USD'))));
    assertFalse($m->isSameCurrency(new Money(100, new Currency('TRY'))));
});

test('Substraction', function () {
    $m1 = new Money(100.10, new Currency('USD'));
    $m2 = new Money(100.02, new Currency('USD'));
    $diff = $m1->subtract($m2);

    assertEquals(new Money(0.08, new Currency('USD')), $diff);
    assertNotSame($diff, $m1);
    assertNotSame($diff, $m2);
});

test('Substraction Mutable', function () {
    $money1 = (new Money(100.10, new Currency('USD')))->mutable();
    $money2 = new Money(100.02, new Currency('USD'));

    $money1->subtract($money2);

    assertEquals((new Money(0.08, new Currency('USD')))->mutable(), $money1);
    assertNotEquals((new Money(0.08, new Currency('USD')))->mutable(), $money2);
});

test('Substration with different currencies', function () {
    $m1 = new Money(100, new Currency('USD'));
    $m2 = new Money(100, new Currency('TRY'));

    $m1->subtract($m2);
})->expectException(InvalidArgumentException::class);

test('Value function', function () {
    assertEquals(new Money(function () {
        return 1;
    }, new Currency('USD')), new Money(1, new Currency('USD')));
});