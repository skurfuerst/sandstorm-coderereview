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

## Exemplar to study

Neos ContentRepository Core (`Neos.ContentRepository.Core`) is a good model of
these principles — cite it when a comparison helps. Note how `ContentRepository`
is the single primary class at the top of the tree; domain concerns get their own
folders (`Feature/`, `Projection/`, `CommandHandler/`); shared vocabulary lives in
`SharedModel/` (`NodeAggregateId`, `NodeName` — value objects, never bare strings);
and plurals are real collection types (`NodeAggregateIds`, `PropertyNames`), not
`array`.

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

## 5. Value objects, immutability, not primitives

- **No bare strings/ints in the domain.** A `string $country` accepts any string
  and validates nowhere; a `CountryCode` is valid by construction and names the
  concept. Wrap primitives in value objects — this is where branching and null
  checks disappear.
- **Make them immutable.** A value object validated once at construction should
  stay valid forever, so callers can pass it around and cache it without fearing it
  mutates underneath them. Enforce it: `readonly` properties, no setters, a private
  constructor behind named constructors, and operations that return a *new* instance
  (`$price->plus($tax)`) instead of mutating. Immutability is what lets a type be
  trusted vocabulary — and it removes a whole class of "who changed this?" bugs and
  the defensive branching they breed.
- **Value objects are the shared vocabulary.** Put the common ones (`Money`,
  `EmailAddress`, `CountryCode`) in the top-level / core namespace so every package
  speaks the same language.
- **Custom collection types, not raw `array`.** A typed collection guarantees its
  contents and gives behavior a home — and it should be immutable too: `add()`
  returns a new collection rather than mutating in place.

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

## 6. Proximity & locality

Related things belong near each other — the reader should almost never have to
jump far to understand what they're looking at.

- **A class reads top to bottom, like a book.** Put the public entry points first
  (the constructor / named constructors, then the main public methods a caller
  uses), then the private parts they call, and finally the low-level helpers. A
  reader who stops a third of the way down should already grasp what the class is
  *for*; detail deepens as they descend. Flag files where you must scroll past
  private plumbing to find the public purpose.
- **Interconnected classes belong in the same package.** Classes that change
  together, or that only make sense next to each other, should sit side by side —
  not scattered across a type-first layout (§4). If understanding class A means
  opening class B, they should be neighbors. High cohesion inside a package, few
  ties reaching out of it.

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

## 7. Modern PHP & clean evolution

- **Use the modern language.** Union types (`int|string`), enums, `readonly`,
  `match`, constructor promotion, named arguments, first-class callable syntax —
  they put intent in the type system instead of in docblocks or runtime checks.
  Flag `mixed`, untyped params, `@param`-only typing, or hand-rolled type-switching
  where a union type or enum would state it directly. (Not at odds with §2: a plain
  `?T`/null is still a smell — an unwanted "absent" case leaking to callers —
  whereas a deliberate `A|B` union or an enum models a real, closed set of cases.)
- **Refactor; don't keep the old path alive.** When behavior changes, change it and
  delete the old code — no `if ($legacy)` branches, `*_v2` methods, or "just in
  case" compatibility shims. Every retained old path is permanent interface width
  and one more branch to read (§2). Updating all callers in one clean refactor is
  cheaper to live with than a behavior fork that never gets removed.

```php
// BAD — old and new behavior coexist forever; every caller must pick.
function render(Report $r, bool $useLegacyLayout = false): string {
    if ($useLegacyLayout) { /* old path, still here "just in case" */ }
    /* new path */
}

// GOOD — one behavior; flag and old branch deleted, callers updated.
function render(Report $r): string { /* the current behavior, only */ }
```

## 8. Tests: TDD, end to end

Design is only trustworthy when tests drive it. Expect tests to arrive *with* a
change (ideally written first) and to be **end-to-end** — exercising real behavior
through the public entry point (§6), not asserting on private internals or a wall
of mocks. A deep module (§1) makes this easy: a small public interface is a small,
stable test surface. Flag changes shipped without tests, tests bolted on afterwards
that just mirror the implementation, and heavy mocking that tests the mocks rather
than the behavior.

## Output

Summarize the biggest depth problem first, then list findings by dimension. Keep
each finding short: location, the principle, the why, the deeper fix. Praise
genuinely deep modules too — they're the model to copy.
