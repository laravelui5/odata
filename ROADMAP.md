# LaravelUi5 OData — Roadmap & Changelog

Tracks scheduled work and released changes for `laravelui5/odata`.

- **Pending** holds items queued for an upcoming slice — design questions settled, scope known. Earlier-stage ideas live as atoms in `docs/meta/atoms/` and are lifted into Pending once they're ready to schedule.
- **CHANGELOG** records shipped releases in reverse-chronological order, each tagged with the version that carried it.

Releases follow the consumer-bump dance: tag the package → Satis rebuilds → patch the in-house consumers (`laravelui5/core`, `laravelui5/sdk`, `pragmatiqu/timesheet.biz`) → smoke-test before the next slice begins. Companion to `laravelui5/core`'s ROADMAP; the two move together when contract surface is shared.

---

## Pending

(No pending items — ROADMAP cleared. Park new ideas as atoms in `docs/meta/atoms/` and lift them into "Pending" when they're ready to schedule.)

---

## CHANGELOG

### PHP backed enum → `Edm.EnumType` (v1.0.4)

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

### `Edm.DateTimeOffset` / `Edm.Date` / `Edm.TimeOfDay` — RFC 3339 wire coercion (v1.0.3)

Row-emission path now coerces values for columns whose declared type is `Edm.DateTimeOffset`, `Edm.Date`, or `Edm.TimeOfDay` to their OData v4 wire formats. Previously, MySQL `DATETIME`/`DATE`/`TIME` strings (`2026-05-05 12:34:56`) passed through unchanged, violating RFC 3339 (`T` + `Z`/offset) and breaking `Date.parse()` in Safari (returns `NaN`); Chrome silently coerced to local time, masking the bug.

Coercion lives in `Protocol\Execution\RowCoercion`, applied by `EntitySetHandler` and `EntityHandler` per row using `Carbon::parse(...)`:

- `Edm.DateTimeOffset` → `->toRfc3339String()`
- `Edm.Date`           → `->toDateString()` (`Y-m-d`)
- `Edm.TimeOfDay`      → `->format('H:i:s')`

`null` values on nullable columns pass through; already-correct strings round-trip cleanly.

**Wire-format change** — UI5 consumers that work around the bug today (`targetType: 'any'` on the binding, or a custom formatter that re-parses MySQL strings) should drop those workarounds once on this version.

### Rename `PrimitiveTypeEnum` → `EdmPrimitiveType` (v1.0.2)

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
