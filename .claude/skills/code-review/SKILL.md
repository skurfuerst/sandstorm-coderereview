---
name: code-review
description: Review code for design quality using "A Philosophy of Software Design" (Ousterhout) — module depth, complexity smells, why-comments, package structure, and value objects over primitives. Use whenever the user asks for a code review, a design/quality review, or to critique the structure of PHP (or similar OO) code, a class, a package, or a diff. Prefer this over a generic review whenever design quality matters.
---

# Code Review: Deep Modules

One idea drives this review: **modules should be deep**. Every review comment
below is a consequence of it. Examples are PHP; the principles are general.

## The core idea

A module (a method, a class, a package) has an **interface** (what a caller must
understand to use it) and an **implementation** (what it does inside).

- **Deep** = simple interface hiding substantial functionality. High value: the
  caller learns a little, gets a lot. *We want deep modules everywhere — method,
  class, and package level.*
- **Shallow** = interface nearly as complex as the implementation. Low value: it
  costs almost as much to use as to write inline. Many tiny pass-through classes
  ("classitis") are a net loss — each adds interface cost, hides nothing.

Depth is the ratio, not size. A one-line method is deep if its name lets callers
forget a real decision; a 300-line class is shallow if callers must know its guts.

```php
// SHALLOW — interface as wide as the implementation, hides nothing.
final class UserData {
    public function getName(User $u): string  { return $u->name; }
    public function getEmail(User $u): string { return $u->email; }
}

// DEEP — tiny interface, absorbs parsing, validation, normalization.
final class EmailAddress {
    private function __construct(public readonly string $value) {}
    public static function fromString(string $raw): self {
        // punycode host, lowercase, reject malformed — caller never sees any of it
    }
}
```

At **package** level the same holds: a package is deep when a caller imports one
primary class and stays ignorant of the dozens behind it.

## How to review

Read the code, then report findings grouped by the dimensions below. For each:
name the location (`path:line`), state which principle it breaks, explain the
**why**, and show the deeper alternative. Lead with the design issues that make
the module shallow — those matter more than style nits.

## 1. Module depth

Flag shallow modules: pass-through getters/setters, `Manager`/`Helper`/`Util`
classes that only delegate, methods whose body restates their one call. Ask "what
decision does this let the caller forget?" If none, it should be inlined or
deepened. Prefer few deep classes over many shallow ones.

## 2. Complexity smells

These usually mean a special case leaked out of a module that should have swallowed
it. Treat them as symptoms, then find the shallow module behind them.

- **Many `if`s / branching** on the same data → missing polymorphism or a missing
  value object that normalizes the cases.
- **Ternaries**, especially nested → a decision the caller shouldn't be making.
- **Nullable types (`?T`)** → `null` forces every caller to handle the empty case.
  A deep module absorbs it (Null Object, an always-valid default, an empty
  collection).

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

## 3. Comments — explain *why*, not *what*

The code should already say *what* it does; a comment that restates it is noise
that rots. Comments earn their place by capturing the **why** — the constraint, the
tradeoff, the non-obvious reason (antirez's "why comments").

Focus on **class-level** comments. Each class should either carry a substantial
*why* comment (what problem it exists to solve, what invariant it guards, what it
deliberately hides) **or** a `@see` link to the class that holds that rationale.
Code inside the class should then read plainly enough to need few inline comments.

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

## 4. Naming & package structure

- **Names carry weight.** A precise name is a small deep interface; a vague one
  (`data`, `manager`, `process`) forces readers into the body.
- **Top level = domain (functional), not type.** Avoid top-level `Controller/`,
  `Model/`, `Service/` folders. Split first by what the code is *about* (`Billing`,
  `Shipping`), then use a **second** level for type (the MVC-ish split).
- **Importance flows down the tree.** The primary, most-general class sits at the
  top of its package; specific, deep-implementation classes live deeper. DTOs
  usually get their own `Dto/` subdirectory.

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

## 5. Value objects, not primitives

- **No bare strings/ints in the domain.** A `string $country` accepts any string
  and validates nowhere; a `CountryCode` is valid by construction and names the
  concept. Wrap primitives in value objects — this is where branching and null
  checks disappear.
- **Value objects are the shared vocabulary.** Put the common ones (`Money`,
  `EmailAddress`, `CountryCode`) in the top-level / core namespace so every package
  speaks the same language.
- **Custom collection types, not raw `array`.** A typed collection guarantees its
  contents and gives behavior a home.

```php
// BAD — primitives, no guarantees, behavior scattered at call sites.
function ship(string $country, array $items): void {}

// GOOD — types make invalid states unrepresentable; LineItems owns its own logic.
function ship(CountryCode $country, LineItems $items): void {}

final class LineItems implements IteratorAggregate {
    /** @param list<LineItem> $items */
    private function __construct(private array $items) {}
    public function total(): Money { /* sum lives with the collection */ }
}
```

## Output

Summarize the biggest depth problem first, then list findings by dimension. Keep
each finding short: location, the principle, the why, the deeper fix. Praise
genuinely deep modules too — they're the model to copy.
