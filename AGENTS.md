## Overview
This repository hosts `magicsunday/xmlmapper` — a standalone PHP library that renders a strongly-typed PHP object as an XML document. It has no framework and no webtrees coupling; it is consumed as a Composer package.

The public surface is small: an object implements the `MagicSunday\XmlSerializable` marker interface, `XmlEncoder::map()` turns it into XML, and three attributes plus an optional name converter steer how each property is rendered.

## Setup/env
- PHP 8.3+ with the `dom` and `xml` extensions. Composer installs into `.build/vendor` (**not** `vendor/`) — remember this when pointing tools at dependencies or when reading a stack trace.
- **All PHP and Composer commands run inside the webtrees Docker buildbox, never on the NAS host:**
  ```
  cd /volume2/docker/webtrees && docker compose run --rm \
      -v /volume2/docker:/var/docker -e COMPOSER_AUTH buildbox \
      composer -d /var/docker/xmlmapper <script>
  ```
- There is no Makefile and no build step; the library ships source only. Node is the one non-PHP dependency: `ci:test:php:cpd` runs `npx jscpd`, and a `post-update-cmd` installs it via `npm install jscpd@^5.0.11`. A `ci:test` that dies at the cpd step outside the buildbox is a missing prerequisite, not a broken script.
- `composer.lock` is **not** committed. CI therefore resolves dev dependencies fresh on every run, so a caret-ranged dev tool can pick up a newer version in CI than a local install has. A green local run is not by itself evidence that CI will be green.

## Build & tests
- **`composer ci:test` MUST pass before every commit.** It chains lint → unit → phpstan → rector → cgl → cpd.
- Individual gates: `ci:test:php:lint`, `ci:test:php:unit`, `ci:test:php:phpstan`, `ci:test:php:rector`, `ci:test:php:cgl`, `ci:test:php:cpd`.
- Single test: `composer ci:test:php:unit -- --filter <TestName>`.
- Auto-fix: `composer ci:cgl` (PHP-CS-Fixer), `composer ci:rector`. Run them until stable — a fix can create new work for the other.
- Coverage: `composer ci:test:php:unit:coverage`.
- PHPStan runs at **level max** with strict-rules over `src/` **and** `tests/`. Test code is held to the same bar as production code; a fixture that only satisfies the analyser is a smell.
- The GitHub build job invokes the granular `ci:test:php:*` steps individually on a `8.3 / 8.4 / 8.5` matrix — it does **not** call the `ci:test` aggregate. A new gate wired only into the aggregate runs locally but never in CI.

## Architecture

```
XmlSerializable (marker interface)
  → XmlEncoder::map($instance): string|false
      → PropertyInfoExtractor  (which properties exist, and of what type)
      → PropertyNameConverterInterface  (optional: class and property names)
      → DOMDocument            (element construction, escaping, serialization)
```

### `src/`
- **`XmlSerializable.php`** — Marker interface. Every object passed to `map()`, and every nested object that should be encoded recursively, must implement it. A *nested* object without it renders as an empty element rather than raising; the root is type-hinted `XmlSerializable`, so a non-marker root is a `TypeError` at the call site, not a silent empty document.
- **`XmlEncoder.php`** — The whole encoder (~600 lines, one class). `map()` builds a fresh `DOMDocument` per call and restores the previous one in a `finally`, so a nested `map()` from inside a custom-type closure does not corrupt the outer document. `encodeElement()` walks the extractor-reported properties; `encodeValue()` stringifies leaves.
- **`XmlMapper/Annotation/`** — `XmlAttribute`, `XmlNodeValue`, `XmlCDataSection`. Resolved through `ReflectionProperty::getAttributes()` only — a docblock spelling is **not** read — and memoised per class and property.
- **`XmlMapper/Converter/`** — `PropertyNameConverterInterface` plus the `CamelCasePropertyNameConverter` default. A converter is a documented extension point, so it may return a name that is not a valid XML name; that surfaces as a `DOMException`.

### `tests/`
- `XmlEncoderTest.php` is a characterization suite over the encoder; `tests/Fixture/` holds ~33 small fixtures, each pinning one behaviour.
- `TestCase.php::getXmlEncoder()` builds the **documented** extractor wiring. Changing it changes the configuration every test runs under — treat it as a shared harness, not a convenience.

### `docs/`
`API.md` is the reference; `recipes/` covers markers, collections, custom name converters, type converters and manual instantiation. `README.md` is the entry point and must not drift out of step with them — a change to the API or a recipe updates the README in the same commit.

## Key patterns
- **The extractor decides what is *listed*; the backing field decides what is *encoded*.** `ReflectionExtractor` reports anything reachable through a public accessor, but `encodeElement()` skips every reported name `hasProperty()` does not know — a getter-only virtual property is silently dropped (pinned by `encodesWhatTheExtractorReportsAndReadsItAsAField`). For names that do have a field, the encoder reads the **field**, not the accessor, so a private field with a public getter is serialized with its raw value even when the getter masks it. There is no per-property opt-out.
- **Type extractors drive two things**: collection detection *and* the `addType()` lookup key. Adding or removing one silently changes which converter fires.
- **`addType()` keys on the declared type.** A fully qualified class or interface name is matched first, `object` remains the catch-all. The registered name is compared against the property's **own declared type** — the hierarchy is not walked and a collection is not unwrapped. A miss is silent: it yields an empty element, or, if the class implements `XmlSerializable`, the fully walked object.
- **Unmappable values are dropped, not signalled.** `encodeValue()` returns `''` for anything neither scalar nor `Stringable`.

## Testing rules
- Write the failing test first, then the minimal fix — including for "obvious" changes.
- **`assertXmlStringEqualsXmlString()` normalises away entity spelling, whitespace, attribute order and the XML declaration.** It is the wrong assertion whenever any of those *is* the dimension under test. Parse the output back (`parseDocumentElement()`) or compare exact bytes instead. Several bugs in this repo survived precisely because a normalising assertion hid them.
- A test must **discriminate**: mutate the production path it covers and confirm the test dies. A test that passes with the fix reverted pins nothing.
- Prefer a fixture that differs from its neighbour in exactly one dimension — `Money` vs. `SerializableMoney` differ only by the marker interface, and that difference is the whole point.
- Characterization tests that do not fail on pre-change code are legitimate, but say so in the docblock so a reader does not mistake them for regression guards.

## Code style
- PER-CS 2.0 (`.php-cs-fixer.dist.php` enables `@PER-CS2x0` on top of `@PSR12`), `declare(strict_types=1)` in every file, `use function` for built-in functions and `use const` / a leading `\` for global constants.
- **No new `mixed`** where the type is knowable. Four signatures on the encoder's value path are irreducibly `mixed` and stay that way — `encodeCollection()`, `encodeObjectOrScalar()`, `encodeValue()`, `callCustomClosure()` receive arbitrary property values and arbitrary custom-closure returns. They are green at level max; do not "narrow" them.
- No `empty()`, no nested ternaries. Explicit comparisons (`=== ''`, `=== []`, `=== null`).
- **Never `@phpstan-ignore`.** Fix the type. `@phpstan-var` on a fixture is a narrowing annotation, not a suppression — and note `PhpDocExtractor` reads only `var`, `param` and `return`, so `@phpstan-var` is invisible to it (useful when a fixture must stay annotation-free at runtime).
- Attributes go **after** the PHPDoc block. Class constants before properties before the constructor before methods.
- Every method and every constant gets a real PHPDoc; `@param`/`@return`/`@throws` descriptions start capitalised. Never write a literal `@tag` in docblock prose — the parser treats it as a real annotation.
- Class docblocks end with the `@author` / `@license` / `@link` triple.
- In `&&`/`||`, parenthesize comparison and `instanceof` operands only — never a bare method call, never a unary `!`. Break multi-operand conditions one operand per line with the operator at line start.
- Comments, commit messages and GitHub prose are English.

## Commits & PRs
- A subject starting with `GH-` must match `^GH-\d+: [A-Z]`; every other subject must match `^[A-Z]` — a capitalised imperative either way. The patterns check only the leading capital; two starts are banned whatever their case: **conventional-commit prefixes** (`feat:`, `Fix:`, `chore:` …) and path-like starts (`src/XmlEncoder.php: …`, `Src/XmlEncoder.php: …`). Because commit messages are English (see [Code style](#code-style)), the leading capital is always `A-Z`.
    - The two patterns are deliberately kept separate: `^(GH-\d+: )?[A-Z]` (wrong) stops enforcing the capital *after* the prefix, because the optional group can be skipped and the `G` of `GH-` then satisfies `[A-Z]` on its own — `GH-12: fix typo` would pass. Keying on the subject rather than on the branch also keeps this check decidable for commits already on `main`, where the issue branch no longer exists.
    - The shared gate in `magicsunday/.github/.github/workflows/commit-convention.yml@main` matches the wider `[[:upper:]]` for that character class on purpose. The capital *match* sits only in accept positions, so widening it can only add PASSes, never produce a false block; the byte-based `tr` in that workflow uses the class too, but on ASCII only. Narrowing it to `[A-Z]` would buy nothing and turn a documentation change into a behavioural one. This holds for the capital class alone — the gate is **not** a superset of `^[A-Z]`, because the `GH-` routing and the two banned starts above reject capitalised subjects too. No workflow here calls that gate, so the rule in this repository is documentation only.
- Branches for an issue are named exactly `GH-<N>`, where `<N>` is the issue number. The `GH-<N>: ` prefix marks work that belongs to the issue, so a commit on that branch whose concern is something else — a drive-by lint fix, a dependency bump — keeps its own unprefixed subject. Merge and revert commits keep the subject Git generates.
- The PR body closes the issue with a `Closes #<N>` keyword. The `GH-<N>: ` subject prefix is not a GitHub link and closes nothing.
- Never add a `Co-Authored-By:` trailer or any AI attribution.
- One concern per commit; style-only fixes stay separate from behaviour changes.
- Every issue gets a type label **and** a `priority:` label from the repo's own set.
- Merging several issue branches in sequence: merge `main` **into** the branch rather than rebasing — a rebase re-resolves the same conflict once per commit. After the last merge, verify that late edits to *existing* methods survived; a resolution that only appends missing methods silently reverts them.

## When stuck
- `composer run-script --list` for the available gates; `docs/API.md` for the intended behaviour.
- Behaviour questions are cheapest to settle with a throwaway probe run in the buildbox against `.build/vendor/autoload.php` — put it in a scratch directory, never in the worktree.
