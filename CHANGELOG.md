# Changelog

All notable changes to `laravelui5/odata` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Entries are tagged with the version that carried them, in reverse-chronological
order. The companion `ROADMAP.md` tracks scheduled, not-yet-shipped work.

## [3.0.1] – 2026-07-20

`4xx` OData errors no longer spam the application error log. A `ProtocolException` whose HTTP
status is `< 500` — a malformed query (`400`), an unauthorized read (`403`), an unknown set
(`404`) — is an **expected client outcome**, not a server fault, so `ProtocolException::report()`
now suppresses Laravel's default error logging for it. `5xx` (a genuine engine failure — `500`,
`501`) is still reported.

**A patch, not a minor.** No public API changes and the HTTP responses are byte-identical — only
the server-side log severity changes. This corrects a pre-existing defect (4xx logged at `ERROR`
with a full stack trace), surfaced now because the `2.1.0` read-authz feature makes `403` a
routine outcome — every denied read was flooding the log with a ~60-frame trace.

Keyed on `httpCode < 500` in the base `ProtocolException`, so it is correct for every subclass,
including future ones. Follows Laravel's `report()` contract (a non-false return skips default
logging). Hosts that *want* 4xx logged can still add their own `reportable()` callback.

## [3.0.0] – 2026-07-20

`ResolverBindingInterface` gains `getSourceClass(): ?string` — the authored class-string that
backs an entity set, so a consumer can reflect class attributes (permissions, capabilities,
annotations) on the right class without knowing the binding taxonomy.

**BREAKING:** `ResolverBindingInterface` gains a required method. The four shipped bindings
implement it; any external implementor of the interface must add it. This is a public MIT package
on strict SemVer (see [2.0.0]), so a new interface method is a **major** — the bump a `^2`
constraint excludes. In-house consumers upgrade deliberately (`core`, `sdk`, `timesheet` — the same
three that moved for 2.0.0).

### Why

`getSourceClass()` returns the class the developer **authored**, which is not always the runtime
resolver `createResolver()` builds:

| Binding            | `createResolver()` (runtime resolver)      | `getSourceClass()` (authored class) |
|:-------------------|:-------------------------------------------|:------------------------------------|
| `EloquentBinding`  | `EloquentEntitySetResolver` (generic)      | the **Eloquent model**              |
| `CustomBinding`    | the app's resolver class                   | that class                          |
| `SqlSourceBinding` | `SqlEntitySetResolver` wrapping the source | the **source** class                |
| `SqlBinding`       | built from a table name                    | `null` — no authored class          |

A consumer that wants to read a class attribute on an entity set (e.g. an SDK read-authorization
gate reflecting a `#[Read]` attribute) needs the **authored** class — the model for a
`discoverModel` set, the custom/source class otherwise. Reflecting the *resolver* would find the
generic `EloquentEntitySetResolver` for every model-backed set and miss the attribute entirely.
`getSourceClass()` names the authored class directly, so the harvest is one loop over
`ResolverMap::getBindings()` with no binding-type matching. `null` marks a raw table/view (no class
to reflect on — gate it by giving it a `SqlSourceBinding` with a source class instead).

### Upgrade

Implement `getSourceClass(): ?string` on any custom `ResolverBindingInterface` — return the
class-string your binding reflects on, or `null` if none:

```php
public function getSourceClass(): ?string
{
    return $this->myBackingClass;   // or null for a class-less binding
}
```

Consumers using only the four shipped bindings need no change.

## [2.1.0] – 2026-07-20

Read authorization comes to OData as a pluggable seam. OData stays security-agnostic, the default is
a no-op, so an authenticated actor who reaches a URL still gets the rows exactly as before. A host that
knows about actors and permissions binds an enforcer and gains a hard **403** gate on any read, plus an
honest-partial response for a gated `$expand`.

**Purely additive → a minor.** New contracts and classes only; the default `AllowAllReadAuthorizer`
records no verdict, so every existing consumer is byte-identical (the full suite proves it). No public
signature broke — `EntitySetSourceInterface::query()` and the `Engine` / handler pipeline are untouched.
Contrast [2.0.0], a major for a breaking `query()` change: this one a `^2` constraint picks up on
`composer update` with no behaviour change.

### The seam — `Service\Contracts\ReadAuthorizerInterface`

A forward-exit in `OData::forService` (and each `$batch` inner request), called after planning and
before execution:

```
authorize(QueryPlanInterface $plan, Request $request, ReadContext $read): void
```

The authorizer records verdicts into a **`Service\ReadContext`** (the read-side collector); the engine
never learns about authorization. Default binding: **`Service\AllowAllReadAuthorizer`** (records nothing
→ read proceeds), overridable via the new **`odata.read_authorizer`** config key or by rebinding the
interface.

### What a verdict does

- **Hard denial** (`denyHard`) on a primary / root target → a standard OData **403** error envelope
  (`Exception\ForbiddenException` → `{"error": {…}}`) carrying the message. The UI5 v4 model surfaces a
  failed request's error body to the message model natively — the reliable carrier for a root denial.
- **Drop** (`denyDrop`) on an `$expand` target → the gated expand is **pruned** and the allowed sets
  still serve (**200**); each drop is reported in a standard **`sap-messages`** response header (an
  unbound warning the v4 model ingests natively). Read authorization is per entity **set**, so a dropped
  set name removes every expand pointing at it, at **any depth** — no bypass via nesting.
- No verdict → served as-is.

`$batch` inner requests run through the identical gate (`Http\ReadGate`), so a denied inner request
produces a per-inner 403 while its siblings serve normally.

### Adopt

Bind your enforcer — it decides off the plan (downcast to `EntitySetQueryPlan` / `EntityQueryPlan` for
`->target`) and your own actor context:

```php
// config/odata.php
'read_authorizer' => \App\OData\MyReadAuthorizer::class,

// or rebind in a service provider
$this->app->bind(ReadAuthorizerInterface::class, MyReadAuthorizer::class);
```

Emit `Service\ReadMessage`s in the standard SAP shape (`code`, `message`, `numericSeverity`, `target`,
`longtextUrl`); resolve the text server-side, and give **unbound** messages `target: ""` (a
non-resolvable target is dropped by the v4 model).

### New surface

- `Service\Contracts\ReadAuthorizerInterface` · `Service\AllowAllReadAuthorizer`
- `Service\ReadContext` · `Service\ReadMessage`
- `Exception\ForbiddenException`
- `Http\ReadGate` (the shared authorize-then-execute path)
- `Protocol\Planning\ExpandPruner` + `withExpand()` on `EntitySetQueryPlan` / `EntityQueryPlan` / `ExpandItem`
- config key `odata.read_authorizer`

## [2.0.0] – 2026-07-02

Custom query options now reach an entity set's `query()` as data on the request — correct inside a
`$batch`, not just on direct GETs.

**BREAKING:** `EntitySetSourceInterface::query()` gains a required parameter —
`query(CustomQueryOptions $options): Builder`. Every custom entity set / source updates its signature
(mechanical; sources that don't use custom options just ignore the arg). This is a public MIT package
that follows SemVer, so a breaking change to a public contract is a **major** — the only bump a `^1`
constraint excludes, which is what keeps downstream consumers from breaking on `composer update`.
In-house consumers upgrade deliberately: `sdk-host` now, the others (`timesheet`, `pragmatiqu.io`)
whenever they choose to pin `^2` — until then they stay on `1.0.7`, unaffected.

### Upgrade

Add the parameter to every `EntitySetSourceInterface::query()` implementation (custom
`AbstractEntitySet`s and any manual sources):

```diff
-public function query(): Builder
+public function query(CustomQueryOptions $options): Builder
```

Sources that consume a custom query option read it straight off the argument —
`$options->get('roleCode')`; sources that don't simply ignore it. Nothing else changes.

An entity set that read a custom query option (a non-`$` URL parameter, e.g. `?roleCode=customer`)
off the global Illuminate request worked on a **direct** request but silently returned unfiltered
results inside a UI5 v4 **`$batch`** — the default client mode. The options live only on each inner
request's URL; the global request during dispatch is the outer `POST …/$batch` envelope, which
carries none of them.

The fix threads them as **data** instead of reaching for global state:

- **`Http\CustomQueryOptions`** — a value object of the non-`$`, non-`@` options, now a property on
  **`Http\ODataRequest`**, populated by both entry points from the parsed URL (`OData::forService`
  for a direct request; `BatchHandler` per inner `$batch` request — so each inner request carries its
  own, no shared state).
- The `QueryPlanner` copies it onto the query plan; `SqlEntitySetResolver` hands it to
  `query($options)`. Entity sets read it directly: `$options->get('roleCode')`.

Guarded by `tests/Service/CustomQueryOptionHttpTest.php` — the same option asserted over a direct GET
and inside a `$batch`.

## [1.0.7] – 2026-06-11

`odata:cache` / `odata:clear` learn `--class`; cache-dir collisions fail loud.

The cache commands discovered services only through the `ODataServiceRegistryInterface`, so a
**route-composed** service (served via `OData::forService()` on its own route, deliberately
outside the registry — see 1.0.6) could not be pre-cached and always ran the cold build path.

Both commands now accept `--class=FQCN1,FQCN2`: the named services are cached / cleared **in
addition** to the registry's, validated (an unknown class or a non-`ODataServiceInterface`
fails), and deduped against the registry.

```bash
php artisan odata:cache --class="App\Excel\ExcelService"
php artisan odata:clear --class="App\Excel\ExcelService"
```

`odata:cache` also gained a **fail-loud pre-pass**: if two services resolve to the same cache
directory (same namespace), it errors before writing anything, rather than letting one
overwrite the other's `Edm/` — which the warm loader would then serve as the wrong schema. (The
complementary load-side guard / identity-based cache keying remains on the ROADMAP.) The
autoloader refresh is now skipped under tests.

## [1.0.6] – 2026-06-11

`OData::forService()` — registry-independent request handling.

The controller resolved its service from the `ODataServiceRegistryInterface` inside
`handle()`, so every service necessarily shared one route group and one middleware
pipeline (`ODataServiceProvider` registers a single `Route::any('{path?}')` under one
prefix). That left no seam for serving a service to a different client over a different
pipeline — e.g. a Basic-auth endpoint for Excel/Power BI beside the session-authenticated
`/odata` space the standard registry serves.

`handle()` now delegates to a new public
`OData::forService(Request, ODataServiceInterface)` — the same request-handling core,
given an already-resolved service. The registry path is unchanged (`handle()` resolves,
then delegates), so the change is **purely additive**. Consumers can compose their own
route with their own middleware and bind a specific service:

```php
Route::any('excel/{path?}', fn (Request $r) =>
    app(OData::class)->forService($r, app(ExcelService::class))
)->where('path', '.*')->middleware('auth.basic');
```

A service mounted on a non-standard prefix must declare that mount by overriding **both**
`route()` (path-stripping) and `endpoint()` (the `@odata.context` / `@odata.nextLink`
service root) — otherwise paginated responses emit next-links into the default `/odata`
namespace and downstream clients (Excel follows `@odata.nextLink`) page into the wrong
place. The standard `/odata` registry space follows the SAP/Microsoft prefix + service +
data convention unchanged; alternative clients get their own namespace + a dedicated service.

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
