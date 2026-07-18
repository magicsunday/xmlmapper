# Custom types

`addType()` registers a closure that transforms every property value of a given
class or builtin type before it is written. It is the hook for formatting values without
changing the model.

```php
public function addType(string $type, Closure $closure): $this;
```

- `$type` is either a **fully qualified class name** or the **resolved builtin
  type name** of the property: `bool`, `int`, `float`, `string`, `array` or
  `object`. A class name is matched first, so a converter can target one value
  object; `object` remains available as the catch-all for every other object
  property.
- The closure signature is `fn (string $name, mixed $value): mixed`. `$name` is the
  (already converted) element name; `$value` is the property value. The returned
  value replaces the original.

The lookup key is derived from the property's declared type, so a closure fires for
*every* property of that type on the encoded graph.

## Formatting a scalar

```php
$encoder->addType(
    'bool',
    static fn (string $name, mixed $value): string => $value === true ? 'yes' : 'no'
);
```

A `public bool $active = true;` property is then rendered as:

```xml
<active>yes</active>
```

## Transforming collections and objects

The `array` and `object` keys match collection-typed and object-typed properties
respectively. The closure runs before the value is encoded, so a returned object is
still encoded recursively and a returned array is still iterated:

```php
$encoder->addType(
    'array',
    static fn (string $name, array $value): array => array_map('strtoupper', $value)
);
```

A `@var string[]` property holding `['a', 'b']` then yields `<items>A</items>` /
`<items>B</items>`.

## Targeting one class

Registering under a class name applies the closure to properties of that class
only:

```php
$encoder
    ->addType(Money::class, fn (string $name, mixed $value): string => $value->format())
    ->addType('object', fn (string $name, mixed $value): string => '[object]');
```

`Money` properties go through the first closure, every other object property
through the second. Without the class key, both would collapse onto `object`.

A class key matches the property's **own declared type** only — it is not
resolved through the inheritance chain, so a converter registered for a parent
class does not fire for a property declared as a subclass. A collection of
that class (`@var Money[]`) likewise resolves to the builtin key `array`, so the class
closure is not applied per entry — and because `Money` does not implement
`XmlSerializable`, each entry then renders as an empty element without an error.
Register the collection under `array` and convert the entries yourself:

```php
$encoder->addType(
    'array',
    static fn (string $name, mixed $value): array
        => array_map(static fn (Money $money): string => $money->format(), $value)
);
```

## Union-typed properties

A union or intersection type has no single builtin name, so it is dispatched under
the `string` key. A closure registered with `addType('string', …)` therefore also
fires for a `int|string`-typed property. The encoding of the value itself is
unaffected — see [Collections](collections.md) for how object unions are encoded.
