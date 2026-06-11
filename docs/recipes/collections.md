# Collections

A property whose resolved type is a collection (`array`, `Traversable<…>`, a typed
collection annotation) is iterated, and each entry is encoded under the property's
element name. Annotate the value type so it can be resolved:

```php
/** @var string[] $tags */
/** @var Chapter[] $chapters */
/** @var array<int, Author|Chapter> $members */
```

## Scalar and object collections

```php
final class Book implements XmlSerializable
{
    /**
     * @var string[]
     */
    public array $tags = ['php', 'xml'];

    /**
     * @var Chapter[]
     */
    public array $chapters = [];
}
```

A populated book yields one element per entry:

```xml
<tags>php</tags>
<tags>xml</tags>
<chapters>
  <heading>Intro</heading>
  <number>1</number>
</chapters>
```

The object-versus-scalar decision is made from the **runtime value**: an entry that
implements `XmlSerializable` is encoded recursively, anything else as a scalar
element.

## Nullable collections

A nullable collection (`@var string[]|null`) is unwrapped to its collection type.
When it holds a value each entry is encoded; when it is `null` the property is
skipped entirely (like any other `null` property) and no empty element is emitted.

## Unions of object types

Because routing is value-based, a collection whose value type is a union of objects
is encoded correctly — each entry by its own type:

```php
/**
 * @var array<int, Author|Chapter>
 */
public array $members = [new Author(), new Chapter('X', 9)];
```

```xml
<members>
  <name>Jane Doe</name>
</members>
<members>
  <heading>X</heading>
  <number>9</number>
</members>
```

A union that mixes a scalar and an object (`@var array<int, int|Author>`) is handled
the same way: scalar entries take the scalar path, object entries are encoded
recursively.

## Untyped arrays

A plain `@var array` with no value type falls back to encoding each entry as a
scalar element, so `['a', 'b']` becomes `<misc>a</misc>` / `<misc>b</misc>`.
