# LaravelUi5 OData — Roadmap & Changelog

Infrastructure / DX issues and consumer-facing contract gaps in `laravelui5/odata`. Items here are queued for a focused OData-library slice — none of them are blocking individual consumers today (workarounds exist, the library renders correct data), but each one trims a recurring tax on every entity set in the workspace.

Companion package: `laravelui5/core`'s ROADMAP. The two move together; consumer-bump dance (per `feedback_satis_release_pause.md`) applies here too.

---

## Pending

### [ ] PHP backed enum → `Edm.EnumType`

**Why:** Consumers declare int-backed PHP enums (`App\Enums\LicenseTier`: 1=single, 2=platform, …). Today `columns()` only accepts `PrimitiveTypeEnum::*`, so the column ships as `Edm.Int64` and the wire carries the raw int. UIs render "1" / "2"; the human label has to be reconstructed at every binding site by a `formatter` that duplicates the enum's case map. OData v4 has native enum-type support; this library has the EDM-side scaffolding (`EnumTypeInterface`, `EnumType`, `CsdlSerializer` already emits `<EnumType>`) but no bridge from a consumer's PHP `BackedEnum` to a registered EDM type the entity-set column can reference.

**What needs to ship:** A factory `EnumType::fromBackedEnum(BackedEnum::class)`, a widened `columns()` contract so a column can declare `LicenseTier::class` instead of `PrimitiveTypeEnum::Int64`, and a row-emission coercion that maps int → symbolic member name in the OData JSON output (UI5's `sap.ui.model.odata.type.Enum` parses the short form).

**Design parked at:** [`docs/meta/atoms/ODATA_BACKED_ENUM.md`](../docs/meta/atoms/ODATA_BACKED_ENUM.md). Five open design questions documented there; settle them before code.

**Estimated size:** ~100–200 LOC + 10–15 tests + the design discussion. One focused day plus the release dance.

**First consumers:** M5's `MyLicenseEntitySet` (`tier`, `renewal_mode`); future Sales work in M9 (license tier in upgrade-chain UX); generic — every entity set that today reaches for a UI5 formatter to convert int→label.

---

## CHANGELOG

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
