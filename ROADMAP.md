# LaravelUi5 OData — Roadmap

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

### [ ] `Edm.DateTimeOffset` — emit RFC 3339 strings on the wire

**Why:** When a column is declared `PrimitiveTypeEnum::DateTimeOffset` and the underlying source is a MySQL `dateTime` column accessed via `DB::table()` (raw query builder, no Eloquent casts — the documented pattern in `PARTNER_SCOPES_RECIPE.md`), MySQL hands back `2026-05-05 12:34:56` — space separator, no `T`, no offset. The library currently passes that through to JSON output unchanged, which violates the OData v4 spec (RFC 3339 mandates `T` + `Z` or numeric offset) and breaks `Date.parse()` in Safari (returns `NaN`). Browsers that *do* accept the format (Chrome) silently coerce to local time, masking the underlying bug.

Workaround today: consumer-side `targetType: 'any'` on the binding to skip parsing, or a UI5 formatter. Both are tax on every consumer.

**What needs to ship:** In the row-emission path of `AbstractEntitySet` (or the JSON serializer downstream of it), coerce values for columns whose declared type is `DateTimeOffset` via `Carbon::parse($value)->toRfc3339String()` (preserves offset; safe for values that already round-trip as RFC 3339). Same coercion logic applies to `Edm.Date` (no time, no offset — `->toDateString()` / `Y-m-d`) and `Edm.TimeOfDay` (just time — `->format('H:i:s')`); spec them all in the same patch.

**Estimated size:** ~30–50 LOC + a serialization test per type. Backward-compatible — values that are already correctly formatted round-trip cleanly through `Carbon::parse(...)`.

**Why this is high marginal ROI:** Bites every consumer that declares a datetime column. Slice 1 of M5 hit it for the second time. One small patch in the library nukes the workaround tax permanently across the workspace.

---

### [ ] Rename `PrimitiveTypeEnum` → `EdmPrimitiveType`

**Where:** `LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum`.

**The rant:** the current name describes the *shape of the PHP construct* ("this is an enum that has primitive types in it") rather than *what the enum represents* ("the set of EDM primitive types"). At the consumer site that asymmetry hurts: `'tier' => PrimitiveTypeEnum::Int64` reads as "the column tier has type 'primitive-type-enum-int-64'" — the `Enum` suffix is dead weight, and "PrimitiveType" without an `Edm` prefix is ambiguous in a codebase that also has PHP type enums, JSON-Schema primitive-type concepts, and SAP UI5 type enums.

`EdmPrimitiveType` reads as what it actually is — *the EDM primitive type* — and the consumer site becomes `'tier' => EdmPrimitiveType::Int64`. The `Enum` suffix vanishes (consumers don't care that the PHP shape is an `enum`; they care about the concept). The `Edm` prefix anchors it in the OData/CSDL vocabulary the rest of the namespace already uses (`Edm\Container\…`, `Edm.EnumType`, `Edm.String`).

Sibling consistency: `EnumTypeInterface` is named for what it represents (an EDM enum type), not for being an interface to an enum-type-enum. `PrimitiveTypeEnum` is the lone hold-out, presumably because someone needed to disambiguate the PHP-enum shape from the EDM concept at name-coining time. With the namespace already saying `Edm`, that disambiguation is redundant — `EdmPrimitiveType` is unambiguous *and* shorter.

**What needs to ship:** Rename the type, update every reference inside the library, ship a deprecated alias (one-line `class_alias()` in a bootstrap file, or a class-extension shim) for one minor release so consumer-side bumps don't break in lock-step. After the deprecation window, the alias goes away.

**Estimated size:** ~5 LOC of rename + the alias shim + a CHANGELOG note. Deprecation window: one minor release. Mechanical.

**Coordination:** consumer-side bumps (host apps, fixtures, tests) flip on the next release after the alias lands. The Satis-pause dance applies — but the alias keeps it boring instead of breaking.

---

## Done

(Reserved for future entries — first finished work moves up here with the version it shipped in.)
