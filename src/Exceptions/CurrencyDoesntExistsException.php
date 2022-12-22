<?php

namespace Knppy\Money\Exceptions;

use OutOfBoundsException;

class CurrencyDoesntExistsException extends OutOfBoundsException
{
    public static function create(string $currency): CurrencyDoesntExistsException
    {
        return new self('Currency "' . $currency . '" does not exists.');
    }
}