# Money

This package intends to provide tools for formatting and conversion monetary values in an easy, yet powerful way for 
your projects.

## Installation

You can install this package via composer: 

```bash
composer require knppy/money
```

## Usage

```php
use Knppy\Money\Currency;
use Knppy\Money\Money;

echo new Money(500, new Currency('USD')); // '$5.00' unconverted
echo new Money(500, new Currency('USD'), true); // '$500.00' converted
```

## Testing

```bash
composer test
```