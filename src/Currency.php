<?php

namespace Knppy\Money;

use JsonSerializable;
use Knppy\Money\Exceptions\CurrencyDoesntExistsException;

/**
 * Currency class.
 */
class Currency implements JsonSerializable
{
    /**
     * The currency.
     *
     * @var string
     */
    private string $currency;

    /**
     * The name.
     *
     * @var string
     */
    private string $name;

    /**
     * The code.
     *
     * @var int
     */
    private int $code;

    /**
     * The rate.
     *
     * @var float
     */
    private float $rate;

    /**
     * The precision.
     *
     * @var int
     */
    private int $precision;

    /**
     * The subunit.
     *
     * @var int
     */
    private int $subunit;

    /**
     * The symbol.
     *
     * @var string
     */
    private string $symbol;

    /**
     * Determine if the symbol should be printed first.
     *
     * @var bool
     */
    private bool $symbolFirst;

    /**
     * The decimal mark.
     *
     * @var string
     */
    private string $decimalMark;

    /**
     * The thousand separator.
     *
     * @var string
     */
    private string $thousandsSeparator;

    /**
     * Creates a new currency instance.
     *
     * @param string $currency
     *
     * @return void
     *
     * @throws CurrencyDoesntExistsException
     */
    public function __construct(string $currency)
    {
        $currency = trim(strtoupper($currency));
        $currencies = self::getCurrencies();
        if (!array_key_exists($currency, $currencies)) {
            throw CurrencyDoesntExistsException::create($currency);
        }

        $attributes = $currencies[$currency];
        $this->currency = $currency;
        $this->name = (string)$attributes['name'];
        $this->code = (int)$attributes['code'];
        $this->rate = (float)($attributes['rate'] ?? 1);
        $this->precision = (int)$attributes['precision'];
        $this->subunit = (int)$attributes['subunit'];
        $this->symbol = (string)$attributes['symbol'];
        $this->symbolFirst = (bool)$attributes['symbol_first'];
        $this->decimalMark = (string)$attributes['decimal_mark'];
        $this->thousandsSeparator = (string)$attributes['thousands_separator'];
    }

    /**
     * Returns the currency instance as a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getCurrency() . ' (' . $this->getName() . ')';
    }

    /**
     * Determine if this currency is equal to the given currency.
     *
     * @param Currency $currency
     *
     * @return bool
     */
    public function equals(Currency $currency): bool
    {
        return $this->getCurrency() === $currency->getCurrency();
    }

    /**
     * Get a list with all the currencies.
     *
     * @return array<string, array>
     */
    public static function getCurrencies(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../resources/currencies.json'), true);
    }

    /**
     * Get the currency.
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the code.
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get the rate.
     *
     * @return float
     */
    public function getRate(): float
    {
        return $this->rate;
    }

    /**
     * Get the precision.
     *
     * @return int
     */
    public function getPrecision(): int
    {
        return $this->precision;
    }

    /**
     * Get the subunit.
     *
     * @return int
     */
    public function getSubunit(): int
    {
        return $this->subunit;
    }

    /**
     * Get the symbol.
     *
     * @return string
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * Determine whether to print the currency symbol first.
     *
     * @return bool
     */
    public function isSymbolFirst(): bool
    {
        return $this->symbolFirst;
    }

    /**
     * Get the decimal mark.
     *
     * @return string
     */
    public function getDecimalMark(): string
    {
        return $this->decimalMark;
    }

    /**
     * Get the thousand's separator.
     *
     * @return string
     */
    public function getThousandsSeparator(): string
    {
        return $this->thousandsSeparator;
    }

    /**
     * Get the prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        if (!$this->symbolFirst) {
            return '';
        }

        return $this->symbol;
    }

    /**
     * Get the suffix.
     *
     * @return string
     */
    public function getSuffix(): string
    {
        if ($this->symbolFirst) {
            return '';
        }

        return ' ' . $this->symbol;
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
     * Convert to an array.
     *
     * @return array[]
     */
    public function toArray(): array
    {
        return [$this->currency => [
            'name' => $this->name,
            'code' => $this->code,
            'rate' => $this->rate,
            'precision' => $this->precision,
            'subunit' => $this->subunit,
            'symbol' => $this->symbol,
            'symbol_first' => $this->symbolFirst,
            'decimal_mark' => $this->decimalMark,
            'thousands_separator' => $this->thousandsSeparator,
            'prefix' => $this->getPrefix(),
            'suffix' => $this->getSuffix(),
        ]];
    }

    /**
     * Convert into an json string.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson(int $options = 0):string {
        return json_encode($this->toArray(), $options);
    }
}