# Changelog

All notable changes to `laravelui5/odata` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Entries are tagged with the version that carried them, in reverse-chronological
order. The companion `ROADMAP.md` tracks scheduled, not-yet-shipped work.

## [1.0.5] – 2026-06-05

`odata:cache` entity-set / type name collision.

`EdmxWriter::writeEntitySet()` emitted each cached entity set as `use
{ns}\Types\{TypeName};` followed by `final readonly class {SetName} implements
EntitySetInterface`, then called `{TypeName}::instance()` by short name. When an
entity **set**'s name equalled its entity **type**'s name, the import and the class
declaration collided and PHP fataled `Cannot redeclare class … (previously declared
as local import)` at autoload time — taking the whole app down, not just OData.

Surfaced in production (2026-06-04, `pragmatiqu.io`): the Portal cache fataled on
`MyDraft`, `MyOrganization`, and `MyPortalState` — the three sets whose names equal
their types. Pluralized sets (`MyLicenses`/`MyLicense`, `Tokens`/`Token`, …) escaped
**by luck, not design**; the next singular set name would have re-triggered it. The
inline (cold) path was never affected — it builds the EDM in memory from the
hand-written `*EntitySet` classes and never writes a colliding `class` declaration,
which is why hot-reload development never hit it.

**Fix:** `writeEntitySet()` now references the type by FQN —
`\{$typeFqcn}::instance()` (the FQN was already computed locally) — and drops the
`use {ns}\Types\{TypeName};` line from the generated template. This aligns the method
with the rest of `EdmxWriter`, which already references types by FQN in every other
emitter (singleton / type / complex-type / nav-init). **Generator-only** change: no
runtime behaviour, EDMX shape, or wire format moves — the regenerated classes are
byte-identical except for the type reference. Consumers regenerate their `Edm/`
caches after the bump.

## [1.0.4] – 2026-05-06

PHP backed enum → `Edm.EnumType`.

Bridges PHP int-backed enums to OData v4 `Edm.EnumType`. A column declared as `LicenseTier::class` now lands as `<EnumType>` in `$metadata` and emits the symbolic member name on the wire (`"tier":"Single"` instead of `"tier":1`) — the short form `sap.ui.model.odata.type.Enum` parses by default.

**What ships:**

- `EnumType::fromBackedEnum(string $namespace, string $enumClass): self` factory. Reflects the enum, validates int-backing, defaults `UnderlyingType` to `Edm.Int32`, emits members in declaration order using PHP case names. Throws `\InvalidArgumentException` on non-enum, pure (non-backed), or string-backed enums.
- `ColumnarSchemaInterface::columns()` PHPDoc widened to `array<string, EdmPrimitiveType|class-string<\BackedEnum>>`. `AbstractEntitySet::entityType()` branches on each value: primitive → `PrimitiveType`, class-string → factory call.
- `EdmBuilder::addEntityType()` now walks declared properties and auto-registers any `EnumTypeInterface`-typed property's enum on the schema. Single chokepoint covers `applyCustomEntitySets()`, virtual expands, and any direct caller — consumers never touch `addEnumType()` themselves.
- `EdmBuilder::addEnumType()` is now keyed by qualified name. Identical re-registration is a silent no-op (factory is deterministic, so two entity sets referencing the same backed enum dedupe naturally). A same-qualified-name registration with a different definition throws `\LogicException` — catches the pathological case where two PHP classes (e.g. `App\Enums\Status` and `App\Other\Status`) collide on the EDM short name.
- `Protocol\Execution\RowCoercion` extended: `EnumTypeInterface` properties get a per-property value→name lookup. Unknown ints fall through unchanged (defensive — schema drift becomes visible rather than masked).

**Design picks resolved** (parked atom: `docs/meta/atoms/ODATA_BACKED_ENUM.md`):

- Class-string sentinel (`'tier' => LicenseTier::class`), not a union with EnumType instances.
- Symbolic short form on the wire only — no qualified-long-form toggle.
- Always default `UnderlyingType: Edm.Int32` — no auto-narrowing by case-value range.
- PHP case names used verbatim — display labels remain an i18n / UI concern.
- Same-qualified-name collision throws at registration; identical re-registration is idempotent.

**Out of scope:** string-backed enums, `IsFlags`, complex types, POST/PATCH deserialization (`"Single"` → `1`).

**Migration:**

Purely additive for primitive consumers — existing `EdmPrimitiveType` column declarations continue to work unchanged. To adopt: replace `'tier' => EdmPrimitiveType::Int64` with `'tier' => LicenseTier::class`, then drop any UI5 `formatter` that was reconstructing the label client-side.

One subtle behavior change worth flagging: `addEntityType()` now auto-registers enum types referenced by the entity type. If a consumer was manually pre-registering an `EnumType` with a slightly different definition than what the entity type carried, that latent inconsistency now throws `\LogicException` at build time instead of producing inconsistent `$metadata` silently. Catches a real bug rather than introduces one.

## [1.0.3] – 2026-05-06

`Edm.DateTimeOffset` / `Edm.Date` / `Edm.TimeOfDay` — RFC 3339 wire coercion.

Row-emission path now coerces values for columns whose declared type is `Edm.DateTimeOffset`, `Edm.Date`, or `Edm.TimeOfDay` to their OData v4 wire formats. Previously, MySQL `DATETIME`/`DATE`/`TIME` strings (`2026-05-05 12:34:56`) passed through unchanged, violating RFC 3339 (`T` + `Z`/offset) and breaking `Date.parse()` in Safari (returns `NaN`); Chrome silently coerced to local time, masking the bug.

Coercion lives in `Protocol\Execution\RowCoercion`, applied by `EntitySetHandler` and `EntityHandler` per row using `Carbon::parse(...)`:

- `Edm.DateTimeOffset` → `->toRfc3339String()`
- `Edm.Date`           → `->toDateString()` (`Y-m-d`)
- `Edm.TimeOfDay`      → `->format('H:i:s')`

`null` values on nullable columns pass through; already-correct strings round-trip cleanly.

**Wire-format change** — UI5 consumers that work around the bug today (`targetType: 'any'` on the binding, or a custom formatter that re-parses MySQL strings) should drop those workarounds once on this version.

## [1.0.2] – 2026-05-06

Rename `PrimitiveTypeEnum` → `EdmPrimitiveType`.

Renamed the enum and relocated three sibling interfaces that had been misfiled under `Edm\Contracts\Container\` (the CSDL §13 namespace for `EntityContainer` children).

**Renames:**

| Old | New |
|:---|:---|
| `LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum` | `LaravelUi5\OData\Edm\EdmPrimitiveType` |
| `LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeInterface` | `LaravelUi5\OData\Edm\Contracts\Type\PrimitiveTypeInterface` |
| `LaravelUi5\OData\Edm\Contracts\Container\EnumTypeInterface` | `LaravelUi5\OData\Edm\Contracts\Type\EnumTypeInterface` |
| `LaravelUi5\OData\Edm\Contracts\Container\EnumMemberInterface` | `LaravelUi5\OData\Edm\Contracts\Type\EnumMemberInterface` |

After this, `Edm\Contracts\Container\` cleanly holds only CSDL §13 children: `EntityContainerInterface`, `EntitySetInterface`, `SingletonInterface`, `FunctionImportInterface`, `NavigationPropertyBindingInterface`. Type-level contracts joined their siblings under `Edm\Contracts\Type\`.

**Breaking change** — no `class_alias` shim. Consumers (`core`, `sdk`, `timesheet.biz`) flip in the next release.

## [1.0.1] – 2026-04-22

Support for Laravel 13.

## [1.0.0] – 2026-04-06

First stable release.
