<?php

namespace App\Core;

/**
 * Money is stored in integer minor units (cents) rather than a float, because
 * floating-point rounding silently loses fractions of a cent and those errors
 * accumulate across a basket into visible, legally-relevant discrepancies.
 * All arithmetic therefore stays in integers until the very last formatting step.
 */
final class Money
{
    private function __construct(
        public readonly int $minorUnits,
        public readonly Currency $currency,
    ) {}

    public static function of(int $minorUnits, Currency $currency): self
    {
        return new self($minorUnits, $currency);
    }

    public function plus(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function percentage(Percentage $p): self
    {
        return new self($p->applyToMinorUnits($this->minorUnits), $this->currency);
    }

    private function assertSameCurrency(Money $other): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw new CurrencyMismatch($this->currency, $other->currency);
        }
    }
}
