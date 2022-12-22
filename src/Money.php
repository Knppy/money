<?php

namespace Knppy\Money;

use BadFunctionCallException;
use Closure;
use InvalidArgumentException;
use JsonSerializable;
use Knppy\Money\Exceptions\UnexpectedAmountException;
use OutOfBoundsException;

class Money implements JsonSerializable
{
    const ROUND_HALF_UP = PHP_ROUND_HALF_UP;
    const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    const ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;

    /**
     * Stores the amount.
     *
     * @var int|float
     */
    protected int|float $amount;

    /**
     * Stores the currency.
     *
     * @var Currency
     */
    protected Currency $currency;

    /**
     * Determine whether this money instance is mutable.
     *
     * @var bool
     */
    protected bool $mutable = false;

    /**
     * Stores the locale for the money instance.
     *
     * @var string
     */
    protected static string $locale;

    /**
     * Gets the locale for the money instance.
     *
     * @return string
     */
    public static function getLocale(): string
    {
        if (empty(static::$locale)) {
            static::$locale = 'en_GB';
        }

        return static::$locale;
    }

    /**
     * Sets the locale for the money instance.
     *
     * @param string|null $locale
     *
     * @return void
     */
    public static function locale(?string $locale): void
    {
        static::$locale = str_replace('-', '_', (string)$locale);
    }

    /**
     * Creates a new money instance.
     *
     * @param mixed $amount
     * @param Currency $currency
     * @param bool $convert
     *
     * @return void
     *
     * @throws UnexpectedAmountException
     */
    public function __construct(mixed $amount, Currency $currency, bool $convert = false)
    {
        $this->currency = $currency;
        $this->amount = $this->parseAmount($amount, $convert);
    }

    /**
     * Returns this money instance into a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Adds the amount by a given value or money instance.
     *
     * @param int|float|Money $addend
     * @param int $roundingMode
     *
     * @return Money
     *
     * @throws UnexpectedAmountException
     */
    public function add(int|float|Money $addend, int $roundingMode = self::ROUND_HALF_UP): Money
    {
        if ($addend instanceof Money) {
            $this->assertSameCurrency($addend);

            $addend = $addend->getAmount();
        }

        $amount = $this->round($this->amount + $addend, $roundingMode);

        if (!$this->isMutable()) {
            return new self($amount, $this->currency);
        }

        $this->amount = $amount;

        return $this;
    }

    /**
     * Allocate the amount.
     *
     * @param array<array-key,int|float> $ratios
     *
     * @return array
     *
     * @throws UnexpectedAmountException
     */
    public function allocate(array $ratios): array
    {
        $remainder = $this->amount;
        $results = [];
        $total = array_sum($ratios);

        foreach ($ratios as $ratio) {
            $share = floor($this->amount * $ratio / $total);
            $results[] = new self($share, $this->currency);
            $remainder -= $share;
        }

        for ($i = 0; $remainder > 0; $i++) {
            $results[$i]->amount++;
            $remainder--;
        }

        return $results;
    }


    /**
     * Compare this money instance with the given money instance.
     *
     * @param Money $other
     *
     * @return int
     *
     * @throws InvalidArgumentException
     */
    public function compare(Money $other): int
    {
        $this->assertSameCurrency($other);

        if ($this->amount < $other->amount) {
            return -1;
        }

        if ($this->amount > $other->amount) {
            return 1;
        }

        return 0;
    }

    /**
     * Converts this money instance to given currency.
     *
     * @param Currency $currency
     * @param int|float $ratio
     * @param int $roundingMode
     *
     * @return Money
     *
     * @throws UnexpectedAmountException
     */
    public function convert(Currency $currency, int|float $ratio, int $roundingMode = self::ROUND_HALF_UP): Money
    {
        $this->currency = $currency;

        return $this->multiply($ratio, $roundingMode);
    }

    /**
     * Divides the money instance.
     *
     * @param int|float $divisor
     * @param int $roundingMode
     *
     * @return Money
     *
     * @throws UnexpectedAmountException
     */
    public function divide(int|float $divisor, int $roundingMode = self::ROUND_HALF_UP): Money
    {
        $this->assertDivisor($divisor);

        $amount = $this->round($this->amount / $divisor, $roundingMode);

        if (!$this->isMutable()) {
            return new self($amount, $this->currency);
        }

        $this->amount = $amount;

        return $this;
    }

    /**
     * Determine if the given money instance is equal to this money instance.
     *
     * @param Money $other
     *
     * @return bool
     */
    public function equals(Money $other): bool
    {
        return $this->compare($other) == 0;
    }

    /**
     * Format this instance into a string.
     *
     * @return string
     */
    public function format(): string
    {
        $negative = $this->isNegative();
        $value = $this->getValue();
        $amount = $negative ? -$value : $value;
        $thousands = $this->currency->getThousandsSeparator();
        $decimals = $this->currency->getDecimalMark();
        $prefix = $this->currency->getPrefix();
        $suffix = $this->currency->getSuffix();
        $value = number_format($amount, $this->currency->getPrecision(), $decimals, $thousands);

        return ($negative ? '-' : '') . $prefix . $value . $suffix;
    }

    /**
     * Format (readable for humans) into a string.
     *
     * @param string|null $locale
     * @param Closure|null $callback
     *
     * @return string
     */
    public function formatForHumans(?string $locale = null, ?Closure $callback = null): string
    {
        // @codeCoverageIgnoreStart
        if (!class_exists('\NumberFormatter')) {
            throw new BadFunctionCallException('Class NumberFormatter not exists. Require ext-intl extension.');
        }
        // @codeCoverageIgnoreEnd

        $negative = $this->isNegative();
        $value = $this->getValue();
        $amount = $negative ? -$value : $value;
        $prefix = $this->currency->getPrefix();
        $suffix = $this->currency->getSuffix();

        $formatter = new \NumberFormatter($locale ?: static::getLocale(), \NumberFormatter::PADDING_POSITION);

        $formatter->setSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, $this->currency->getDecimalMark());
        $formatter->setSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, $this->currency->getThousandsSeparator());
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $this->currency->getPrecision());

        if (is_callable($callback)) {
            $callback($formatter);
        }

        return ($negative ? '-' : '') . $prefix . $formatter->format($amount) . $suffix;
    }

    /**
     * Format (locale) this instance into a string.
     *
     * @param string|null $locale
     * @param Closure|null $callback
     *
     * @return string
     */
    public function formatLocale(?string $locale = null, ?Closure $callback = null): string
    {
        // @codeCoverageIgnoreStart
        if (!class_exists('\NumberFormatter')) {
            throw new BadFunctionCallException('Class NumberFormatter not exists. Require ext-intl extension.');
        }
        // @codeCoverageIgnoreEnd

        $formatter = new \NumberFormatter($locale ?: static::getLocale(), \NumberFormatter::CURRENCY);

        $formatter->setSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, $this->currency->getDecimalMark());
        $formatter->setSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, $this->currency->getThousandsSeparator());
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $this->currency->getPrecision());

        if (is_callable($callback)) {
            $callback($formatter);
        }

        return $formatter->formatCurrency($this->getValue(), $this->currency->getCurrency());
    }

    /**
     * Format (simple) this instance into a string.
     *
     * @return string
     */
    public function formatSimple(): string
    {
        return number_format(
            $this->getValue(),
            $this->currency->getPrecision(),
            $this->currency->getDecimalMark(),
            $this->currency->getThousandsSeparator()
        );
    }

    /**
     * Format (without zeros) this instance into a string.
     *
     * @return string
     */
    public function formatWithoutZeroes(): string
    {
        if ($this->getValue() !== round($this->getValue())) {
            return $this->format();
        }

        $negative = $this->isNegative();
        $value = $this->getValue();
        $amount = $negative ? -$value : $value;
        $thousands = $this->currency->getThousandsSeparator();
        $decimals = $this->currency->getDecimalMark();
        $prefix = $this->currency->getPrefix();
        $suffix = $this->currency->getSuffix();
        $value = number_format($amount, 0, $decimals, $thousands);

        return ($negative ? '-' : '') . $prefix . $value . $suffix;
    }

    /**
     * Get the amount.
     *
     * @param bool $rounded
     *
     * @return float|int
     */
    public function getAmount(bool $rounded = false): float|int
    {
        return $rounded ? $this->getAmountRounded() : $this->amount;
    }

    /**
     * Get the amount rounded.
     *
     * @return int|float
     */
    public function getAmountRounded(): int|float
    {
        return $this->round($this->amount);
    }

    /**
     * Get the currency.
     *
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * Get the value.
     *
     * @return float
     */
    public function getValue(): float
    {
        return $this->round($this->amount / $this->currency->getSubunit());
    }

    /**
     * Determine if the given money instance is greater than this money instance.
     *
     * @param Money $other
     *
     * @return bool
     */
    public function greaterThan(Money $other): bool
    {
        return $this->compare($other) == 1;
    }

    /**
     * Determine if the given money instance is equal or greater than this money instance.
     *
     * @param Money $other
     *
     * @return bool
     */
    public function greaterThanOrEqual(Money $other): bool
    {
        return $this->compare($other) >= 0;
    }

    /**
     * Make this money instance immutable.
     *
     * @return Money
     *
     * @throws UnexpectedAmountException
     */
    public function immutable(): Money
    {
        $this->mutable = false;

        return new self($this->amount, $this->currency);
    }

    /**
     * Determine whether the amount is mutable.
     *
     * @return bool
     */
    public function isMutable(): bool
    {
        return $this->mutable === true;
    }

    /**
     * Determine whether the amount is negative.
     *
     * @return bool
     */
    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Determine whether the amount is positive.
     *
     * @return bool
     */
    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Determine whether the given money object is having the same currency.
     *
     * @param Money $other
     *
     * @return bool
     */
    public function isSameCurrency(Money $other): bool
    {
        return $this->currency->equals($other->currency);
    }

    /**
     * Determine whether the amount is zero.
     *
     * @return bool
     */
    public function isZero(): bool
    {
        return $this->amount == 0;
    }

    /**
     * Make this money instance mutable.
     *
     * @return $this
     */
    public function mutable(): Money
    {
        $this->mutable = true;

        return $this;
    }

    /**
     * Convert to json.
     *
     * @return array[]
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Determine if the given money instance is less than this money instance.
     *
     * @param Money $other
     *
     * @return bool
     */
    public function lessThan(Money $other): bool
    {
        return $this->compare($other) == -1;
    }

    /**
     * Determine if the given money instance is equal or less than this money instance.
     *
     * @param Money $other
     *
     * @return bool
     */
    public function lessThanOrEqual(Money $other): bool
    {
        return $this->compare($other) <= 0;
    }

    /**
     * Multiplies the amount.
     *
     * @param int|float $multiplier
     * @param int $roundingMode
     *
     * @return Money
     *
     * @throws UnexpectedAmountException
     */
    public function multiply(int|float $multiplier, int $roundingMode = self::ROUND_HALF_UP): Money
    {
        $amount = $this->round($this->amount * $multiplier, $roundingMode);

        if (!$this->isMutable()) {
            return new self($amount, $this->currency);
        }

        $this->amount = $amount;

        return $this;
    }

    /**
     * Round the given amount.
     *
     * @param float|int $amount
     * @param int $mode
     *
     * @return float
     *
     * @throws OutOfBoundsException
     */
    public function round(float|int $amount, int $mode = self::ROUND_HALF_UP): float
    {
        $this->assertRoundingMode($mode);

        return round($amount, $this->currency->getPrecision(), $mode);
    }

    /**
     * Subtract the amount by a given value or money instance.
     *
     * @param int|float|Money $subtrahend
     * @param int $roundingMode
     *
     * @return Money
     *
     * @throws UnexpectedAmountException
     */
    public function subtract(int|float|Money $subtrahend, int $roundingMode = self::ROUND_HALF_UP): Money
    {
        if ($subtrahend instanceof Money) {
            $this->assertSameCurrency($subtrahend);

            $subtrahend = $subtrahend->getAmount();
        }

        $amount = $this->round($this->amount - $subtrahend, $roundingMode);

        if (!$this->isMutable()) {
            return new self($amount, $this->currency);
        }

        $this->amount = $amount;

        return $this;
    }

    /**
     * Convert into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'amount'   => $this->amount,
            'value'    => $this->getValue(),
            'currency' => $this->currency,
        ];
    }

    /**
     * Convert into a json string.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Assert the divisor.
     *
     * @param int|float $divisor
     *
     * @return void
     */
    protected function assertDivisor(int|float $divisor): void
    {
        if ($divisor == 0) {
            throw new InvalidArgumentException('Division by zero');
        }
    }

    /**
     * Asserts the rounding mode.
     *
     * @param int $mode
     *
     * @return void
     *
     * @throws OutOfBoundsException
     */
    protected function assertRoundingMode(int $mode): void
    {
        $modes = [self::ROUND_HALF_UP, self::ROUND_HALF_DOWN, self::ROUND_HALF_EVEN, self::ROUND_HALF_ODD];

        if (!in_array($mode, $modes)) {
            throw new OutOfBoundsException('Rounding mode should be ' . implode(' | ', $modes));
        }
    }

    /**
     * Assert whether the given Money object is having the same currency.
     *
     * @param Money $other
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function assertSameCurrency(Money $other): void
    {
        if (!$this->isSameCurrency($other)) {
            throw new InvalidArgumentException('Different currencies "' . $this->currency . '" and "' . $other->currency . '"');
        }
    }

    /**
     * Converts the amount.
     *
     * @param int|float $amount
     * @param bool $convert
     *
     * @return int|float
     */
    protected function convertAmount(int|float $amount, bool $convert = false): int|float
    {
        if (!$convert) {
            return $amount;
        }

        return $amount * $this->currency->getSubunit();
    }

    /**
     * Parse the amount.
     *
     * @param mixed $amount
     * @param bool $convert
     * @return int|float
     *
     * @throws UnexpectedAmountException
     */
    protected function parseAmount(mixed $amount, bool $convert): int|float
    {
        /** @var int|float|Money $amount */
        $amount = $this->parseAmountFromString($this->parseAmountFromCallable($amount));
        if (is_int($amount)) {
            return (int)$this->convertAmount($amount, $convert);
        }

        if (is_float($amount)) {
            return $this->convertAmount($amount, $convert);
        }

        if ($amount instanceof static) {
            return $this->convertAmount($amount->getAmount(), $convert);
        }

        throw new UnexpectedAmountException('Invalid amount "' . $amount . '"');
    }

    /**
     * Parse the amount from a given callable.
     *
     * @param mixed $amount
     *
     * @return mixed
     */
    protected function parseAmountFromCallable(mixed $amount): mixed
    {
        if (!is_callable($amount)) {
            return $amount;
        }

        return $amount();
    }

    /**
     * Parse the amount from a given string.
     *
     * @param mixed $amount
     *
     * @return mixed
     */
    protected function parseAmountFromString(mixed $amount): mixed
    {
        if (!is_string($amount)) {
            return $amount;
        }

        $thousandsSeparator = $this->currency->getThousandsSeparator();
        $decimalMark = $this->currency->getDecimalMark();

        $amount = str_replace($this->currency->getSymbol(), '', $amount);
        $amount = preg_replace('/[^\d\\' . $thousandsSeparator . '\\' . $decimalMark . '\-\+]/', '', $amount);
        $amount = str_replace($this->currency->getThousandsSeparator(), '', $amount);
        $amount = str_replace($this->currency->getDecimalMark(), '.', $amount);

        if (preg_match('/^([\-\+])?\d+$/', $amount)) {
            $amount = (int)$amount;
        } elseif (preg_match('/^([\-\+])?\d+\.\d+$/', $amount)) {
            $amount = (float)$amount;
        }

        return $amount;
    }
}