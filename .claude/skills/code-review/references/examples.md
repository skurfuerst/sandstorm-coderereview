# PHP examples for the code-review dimensions

Concrete before/after patterns for each dimension in `SKILL.md`. Read the section
you need when you want a pattern to point at in a review. The core idea's
shallow-vs-deep example lives inline in `SKILL.md`; everything else is here.

## §2 Complexity smells — absorb the special case

```php
// SMELL — nullability + branching push special cases onto every caller.
function total(Money $base, ?Discount $d, ?Coupon $c): Money {
    $t = $base;
    if ($d !== null) { $t = $d->applyTo($t); }
    if ($c !== null) { $t = $c->applyTo($t); }
    return $t;
}

// DEEPER — Discount is always present (NoDiscount is the neutral case).
function total(Money $base, Discount $d): Money {
    return $d->applyTo($base); // no null, no branch; the type carries the case
}
```

## §3 Comments — why, not what

```php
// BAD — restates the code.
$i++; // increment i

/**
 * Retry auth at most 3 times: the PSP locks the account after 4 failed
 * attempts in 60s, so a 4th retry would lock out a legitimate user.
 * @see PaymentGateway for the surrounding protocol.
 */
final class AuthRetryPolicy { /* ... */ }
```

## §4 Naming & package structure — domain first

```
src/
  Billing/                 # domain — top level
    Invoice.php            # primary class, at the top
    Money.php              # shared value-object vocabulary (see §5)
    Http/                  # second level = type
      InvoiceController.php
    Dto/
      InvoiceView.php
  Shipping/
    Shipment.php
```

Not: `src/Controller/`, `src/Model/`, `src/Service/` (type-first hides the domain).

## §5 Value objects, immutability — types over primitives

```php
// BAD — primitives, no guarantees, mutable, behavior scattered at call sites.
function ship(string $country, array $items): void {}

// GOOD — types make invalid states unrepresentable; immutable; LineItems owns its logic.
function ship(CountryCode $country, LineItems $items): void {}

final class LineItems implements IteratorAggregate {
    /** @param list<LineItem> $items */
    private function __construct(private readonly array $items) {}
    public function withAdded(LineItem $i): self { return new self([...$this->items, $i]); }
    public function total(): Money { /* sum lives with the collection */ }
}
```

## §6 Proximity & locality — a class reads top to bottom

```php
final class AuthRetryPolicy {
    // 1. entry points — what callers use, read first
    public static function default(): self { /* ... */ }
    public function shouldRetry(Attempt $a): bool { return $this->within($a); }

    // 2. private parts, in call order
    private function within(Attempt $a): bool { /* ... */ }

    // 3. low-level helpers last
    private function backoff(int $n): Duration { /* ... */ }
}
```

## §7 Modern PHP & clean evolution — one behavior, no legacy branch

```php
// BAD — old and new behavior coexist forever; every caller must pick.
function render(Report $r, bool $useLegacyLayout = false): string {
    if ($useLegacyLayout) { /* old path, still here "just in case" */ }
    /* new path */
}

// GOOD — one behavior; flag and old branch deleted, callers updated.
function render(Report $r): string { /* the current behavior, only */ }
```
