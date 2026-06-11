# Custom types

`addType()` registers a closure that transforms every property value of a given
builtin type before it is written. It is the hook for formatting values without
changing the model.

```php
public function addType(string $type, Closure $closure): $this;
```

- `$type` is the **resolved builtin type name** of the property: `bool`, `int`,
  `float`, `string`, `array` or `object`.
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

## Union-typed properties

A union or intersection type has no single builtin name, so it is dispatched under
the `string` key. A closure registered with `addType('string', …)` therefore also
fires for a `int|string`-typed property. The encoding of the value itself is
unaffected — see [Collections](collections.md) for how object unions are encoded.
