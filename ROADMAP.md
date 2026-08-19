# LaravelUi5 OData — Roadmap

Tracks scheduled work for `laravelui5/odata`. Shipped releases live in
[`CHANGELOG.md`](./CHANGELOG.md), each tagged with the version that carried it.

- **Pending** holds items queued for an upcoming slice — design questions settled, scope known. Earlier-stage ideas live as atoms in `docs/meta/atoms/` and are lifted into Pending once they're ready to schedule.

Releases follow the consumer-bump dance: tag the package → Satis rebuilds → patch the in-house consumers (`laravelui5/core`, `laravelui5/sdk`, `pragmatiqu/timesheet.biz`) → smoke-test before the next slice begins. Companion to `laravelui5/core`'s ROADMAP; the two move together when contract surface is shared.

---

## Pending

## [ ] EDM cache is keyed by class location, not service identity — load-side silent collision

Surfaced 2026-06-11 (`pragmatiqu/timesheet.biz`) building a second, **route-composed** service
(`ExcelService`, via the 1.0.6 `OData::forService()` seam) beside the registry-cached
`TimesheetService`. `EdmxLoader::cacheDir()` (`dirname(classFile)/Edm`) and `edmxClassName()`
(`{ns}\Edm\Edmx`) key the warm cache by the service class's **directory + namespace**, not by
service identity. Two services sharing a namespace → the second's `schema()` warm path loads the
FIRST's cached EDMX and serves it, **silently**. `ExcelService` returned `TimesheetService`'s full
60-set `$metadata` on `/excel` until it was moved to its own `App\Excel` namespace.

Workaround (in place downstream): one service per namespace.

**Write side shipped (1.0.7):** `odata:cache` now fails loud when two services resolve to the same
cache dir, before writing anything (see Done). **Still pending — the load side:**
`EdmxLoader::forService` should fail loud (or key the cache by `serviceUri`/`route()` / a
service-provided cache key) so a pre-existing or hand-placed collision can't be silently served.
Silent-wrong-schema is the worst failure mode — same family as the 1.0.5 collision.

Atom [[ODATA_ALTERNATIVE_CLIENT_DEDICATED_SERVICE]] · spec `docs/meta/specs/odata-route-composition.md` OP5.

## [ ] SQL-driver serialization emits raw DB scalars, not values coerced to the declared Edm type

Surfaced 2026-07-06 (`laravelui5/sdk` — `sdk-host` Partners `PartnerParametersEntitySet`) adding a
computed `writable_by_actor` column declared `EdmPrimitiveType::Boolean`. A custom entity set's rows
flow `AbstractEntitySet::query()` → `SqlEntitySetResolver` → `->get()` → `EntitySetHandler` (`:84`),
which echoes `json_encode($row)` on the **raw DB row**. Nothing coerces each property to its declared
Edm type, so the wire value is whatever the driver returned. MySQL/SQLite have no native boolean
(`TRUE`/`FALSE` are literals for `1`/`0`), so a `case when … then 1 else 0 end` column declared
`Edm.Boolean` serializes as JSON `1`, not `true`. The `columns()` Edm map drives the `$metadata`
schema (the contract the client reads) but **not** the runtime values — a declared-type-vs-emitted-value
split. A strict client (the UI5 v4 model) trusts the metadata, sees `1` for an `Edm.Boolean` property,
and rejects it (`"1" is of type number, expected boolean`). Same silent-wrong-value family as the cache
items — the schema promises one thing, the value delivers another.

The Eloquent path (`EloquentEntitySetResolver` + a model `'col' => 'boolean'` cast) already yields a
real PHP bool before encoding, so it serializes correctly; the raw `fromSub`/query-builder SQL path has
no such lever.

**Fix:** the SQL driver should coerce each selected property to its declared `EdmPrimitiveType` before
`json_encode` — `Boolean → (bool)`, `Int16/Int32/… → (int)`, `Decimal/Double/Single → (float)`,
preserving null. PHP-side, so it's cross-DB-safe (independent of the driver's return type) and closes the
gap for every SQL-backed custom set (boolean flags, numeric fidelity), not just this one column.

Workaround (in place downstream): model 0/1 flags as `EdmPrimitiveType::Int32` and coerce at the client
boundary (`{= !!${…} }` when binding to a boolean control property).

## [ ] Virtual `$expand` is resolved only on Eloquent-backed sets — custom (SQL) entity sets ignore it

Surfaced 2026-07-08 (`laravelui5/sdk` — `sdk-host` Partners, building the ui5-partners **object-page
header** pattern, PA-DETAILS-A-10). A header binds ONE keyed resource and lets its variable-length
adornments ride along as expanded nav collections — the pattern `pragmatiqu/timesheet.biz`'s `App\OData\Kpis`
proves (`Users(11)?$expand=kpis`). We tried the same on `PartnerDetails` — a **computed `fromSub` custom
set** (identity + count-tab totals) — with a `PartnerStructuralRolesExpand` (`CustomEntitySetInterface` +
`VirtualExpandResolverInterface`) attaching a `structural_roles` collection.

**Metadata wires correctly** — `$metadata` shows the `structural_roles` `NavigationProperty` on
`PartnerDetail` + its `NavigationPropertyBinding` (both `applyVirtualExpandsToDiscovery` and
`applyVirtualExpandsToBuilder` cover it). **Runtime does not:** `resolveExpand()` is invoked **only** from
`EloquentEntitySetResolver::resolveVirtualExpand()` (`:399`). The `SqlEntitySetResolver` that serves custom
`AbstractEntitySet`s (`resolve()` / `resolveOne()`) never reads `$plan->expand`, so the collection is never
materialized — the response omits it entirely (not even `[]`). `Kpis` works only because it expands on
`User`/`Project`, which are `discoverModel` entities served by the Eloquent resolver. **A computed header
resource cannot carry a virtual expand today.**

**Fix:** teach `SqlEntitySetResolver` to apply virtual expands after producing each row — mirror
`EloquentEntitySetResolver`'s `resolveVirtualExpand` + `attachExpandedRelations` (needs a schema handle to
look up `$item->targetSet`'s resolver; check `instanceof VirtualExpandResolverInterface`; attach under the
nav name). Closes the gap for every SQL-backed custom set that wants header-adornment collections, not just
this one — and unblocks the canonical **object-page-header skill**.

**Resolved downstream by not being custom (2026-07-08):** `ui5-partners` reverted `PartnerDetails` from a
computed `fromSub` set to an **Eloquent** `discoverModel(PartnerDetail)` header and moved its counts to
virtual `$expand`s (`authorization` summary, `delegations` collection, `settings` summary) — the
object-page-header pattern (`docs/meta/atoms/OBJECT_PAGE_HEADER_AS_ELOQUENT_EXPAND.md`). So this item is **no
longer blocking** — it stands for the residual case where a header genuinely *must* be a computed set and
still wants expand-able adornments. Until then, prefer an Eloquent header over a custom one.

Same resolver-family gap as the 2026-07-06 Edm-coercion item above (both: `SqlEntitySetResolver` lacks a
capability `EloquentEntitySetResolver` has).

## [ ] A lean / no-hydration path for expand-less `discoverModel` reads — reclaim streaming speed on lists

Surfaced 2026-07-08 (`laravelui5/sdk` — measuring the Partners master/detail migration). The engine's whole
speed advantage over `flat3/lodata` is that it **streams optimized SQL rows and does not hydrate models**.
`SqlEntitySetResolver` (custom sets) does exactly that — `->cursor()` → `stdClass` → `(array)`.
`EloquentEntitySetResolver` (`discoverModel` sets), by contrast, **always** `get()/cursor()` → `Model` →
`toArray()`. Measured on 180 rows (dev SQLite): raw **0.82 ms**, Eloquent cast-free **27.97 ms** (~34×),
Eloquent with enum/date casts **60.77 ms** (~74×). Casts roughly double the cost; enum casts dominate.

Consequence: a `discoverModel` set is the right tool for a **small-N** read (a keyed detail + a few
`$expand`s — hydration is ~1 ms, negligible) but a **speed regression for a large-N list** (a Master, an
export, an aggregate). Today that forces large lists to stay custom raw-SQL sets — correct, but it blocks
the clean "one Eloquent model, one set for master + detail" shape (a list read of the Eloquent set would
hydrate every row). See `docs/meta/atoms/ODATA_STREAMING_NOT_HYDRATION.md`.

**Fix:** when an `EloquentEntitySetResolver` read has **no `$expand`** and no properties needing cast/accessor
transformation (or those can be applied cheaply on the raw row), take a **lean path** — `->toBase()->get()`
(or `->cursor()`) yielding `stdClass`/arrays directly, skipping model instantiation + `toArray()`. Same rows
on the wire, streaming speed. Gate it on the query plan (`$expand` empty) so expand reads keep hydrating (they
need the relations). This would let one discovered set serve a **fast lean list** (`$select`, no expand) AND a
**rich detail** (keyed, `$expand`) — unifying master/detail on Eloquent without the hydration tax.

In the spirit of the engine: the fast path should be the default, hydration the opt-in that expands require.

---

## Done

Shipped items live in [`CHANGELOG.md`](./CHANGELOG.md) under their version. This
section keeps the roadmap-level breadcrumb — the *why it was queued* — for items
that passed through Pending.

## [x] `odata:cache` writes (and first deletes) `Edm/` directories inside `vendor/` (v3.0.3)

Surfaced 2026-08-19 in `pragmatiqu.io`. Same root cause as *EDM cache is keyed by class
location* (still open, under Pending) — but this is the write side, and it reaches outside the
application.

`ResolvesServices::resolveServices()` takes *every* registry service with no regard for where
its class lives, and `CacheCommand` derives the output path as
`dirname($reflected->getFileName()) . '/Edm'`. For a service that ships in a package, that path
is inside `vendor/`. One run in this host produced:

    vendor/laravelui5/auth/src/Edm/
    vendor/laravelui5/sdk/Partners/src/Edm/
    vendor/laravelui5/sdk/Settings/src/Edm/
    vendor/laravelui5/sdk/Launchpad/src/Edm/

**None of these are shipped.** Verified against the source repos: `laravelui5-sdk` and
`laravelui5-auth` track zero files under any `Edm/` path. Every such directory in `vendor/` was
written by a local `odata:cache`.

**Why it must not happen.**

1. **It contradicts the command's own contract.** `odata:cache` refuses to run in production
   with *"The generated Edm/ cache is committed to version control and deployed as-is."* For a
   `vendor/` path that is impossible — nothing there is committed. The dev machine therefore
   runs a **warm** path that production never has, and any defect that only shows on the cold
   path is invisible during development. A cache that changes behaviour only on the developer's
   machine is the wrong way round.
2. **`deleteDirectory` runs first.** A run that throws partway leaves a package's cache deleted.
   Combined with the empty-ResolverMap defect above, this host ended up with 25 silently
   unbound entity sets in `laravelui5/sdk`, which took out 103 feature tests. The only recovery
   was `rm -rf vendor/laravelui5/sdk && composer install` — nothing in the error message points
   there, because the error names the *entity sets*, not the package.
3. **`composer install` silently reverts it.** The artifact is invisible to git, so the state a
   developer debugs is not reproducible for anyone else and disappears on the next update.

**Shape of a fix.** Only write caches for services the application owns: skip any service whose
class file resolves inside the composer vendor directory (or, equivalently, require the output
path to be under `base_path()`), and say so in the run output rather than skipping silently.
Packages that want a shipped cache should generate it in **their own** build, committed to their
own repo — not in a consumer's `vendor/`.

**Shipped 3.0.3.** `ResolvesServices::resolveServices()` drops any service whose class file
sits under the composer vendor directory — both commands inherit it, and each skip is named in
the output. See `CHANGELOG.md` v3.0.3.

## [x] `odata:cache` writes an EMPTY ResolverMap for registry-cached apps — silently disables every entity set (v3.0.3)

Surfaced 2026-08-19 in `pragmatiqu.io` while adding a third entity set to the Sales app.

**Symptom.** Running `odata:cache` rewrites `Edm/ResolverMap.php` for the *host* apps
(`Pragmatiqu\Sales\SalesApp`, `Pragmatiqu\Portal\PortalApp`) as an empty map. Nothing
fails at write time — the command reports `Cached: …` and `OData schema cached successfully.`
The damage shows up on the next request or command as

    RuntimeException: The following entity sets have no resolver bound: Prospects, Customers.

An empty cached ResolverMap **wins over the runtime bindings**, so every set of that service
goes dark. In the host this took out 103 feature tests until the file was restored from git.

**Why the vendor apps escaped.** In the same run, `LaravelUi5\Sdk\Partners\PartnersApp` and
friends got *correct* maps (25 bindings). The difference is where the service instance comes
from: the vendor apps are constructed cold in-process, so `configure()` runs and
`discoverCustomEntitySet()` populates the bindings. The host apps are handed back by the
**ui5 registry cache** (`bootstrap/cache/ui5.php`), whose instances never ran `configure()` —
`$service->resolverMap()` is therefore empty, and `ResolverMapWriter` faithfully writes that.

**Second-order damage.** `CacheCommand` calls `$fs->deleteDirectory($outputDir)` *before*
building, and iterates the whole registry, so a throw partway leaves earlier services rewritten
and later ones with **no** cache at all. Where that lands in `vendor/` it is worse — see the
next item.

**What it makes impossible today.** A newly registered entity set cannot be cached: the
generator either writes an empty map or aborts, and `EdmxLoader` short-circuits the runtime
builder whenever `Edm/Edmx.php` exists — so the new set is invisible to the served schema
while the old cache stands.

**Shape of a fix.** (a) `resolverMap()` must be derived from the same pass that produced the
Edmx, not from whatever the instance happens to carry; (b) refuse to write an empty map when
the schema declares entity sets — fail loud instead of writing a booby-trapped cache;
(c) build every service *before* deleting any cache directory, so a partial run cannot leave
the tree worse than it found it.

### Recognising it, and getting back

The error names entity sets, never the file that broke them — which is why the first hour goes
into the wrong place. Two greps settle it:

```bash
# Any generated map that declares no bindings is a live booby trap.
grep -L 'Binding(' $(find . vendor -path '*/Edm/ResolverMap.php')

# Anything under vendor/ is generated, never shipped — the packages track no Edm/ at all.
find vendor -type d -name Edm
```

Recovery, in this order:

1. **Host apps** — `git checkout -- <app>/src/Edm`. The committed cache is the good one.
2. **Packages** — `rm -rf vendor/<pkg> && composer install`. Nothing under `vendor/*/Edm` is
   version-controlled, so there is nothing to restore; removing it is the repair, and the cold
   path takes over.
3. **Registry cache** — `bootstrap/cache/ui5.php` participates (see *Why the vendor apps
   escaped*). Regenerate it with `ui5:cache` **only after** step 1 and 2, because `ui5:cache`
   itself builds schemas and will abort on a broken map.

Until the fix lands, the working rule for a consumer is blunt: **do not run `odata:cache`.** A
newly registered entity set has to be added to the committed cache by hand — four files:
`Edm/Types/<Type>.php`, `Edm/Entities/<Set>.php`, plus one entry each in `Edm/Edmx.php` and
`Edm/ResolverMap.php`. Mechanical, and it keeps the served schema honest until the generator
can be trusted again.

**Shipped 3.0.3, completed in 3.0.4.** `resolverMap()` is built from `configure()`
unconditionally, out of the same pass that produces the Edmx (`buildFromConfigure()`), with the
accumulators reset so the build is repeatable. 3.0.3 left one half undone — `CacheCommand` still
took the *Edmx* from `schema()`, which memoises and prefers the warm path, so a stale schema
survived every regeneration; 3.0.4 added `buildForCache()` and takes both artefacts from the one
cold pass. `odata:cache` additionally builds every service before deleting any cache
directory, so a failure part-way leaves the tree as it found it. See `CHANGELOG.md` v3.0.3.

## [x] `EdmxWriter` flattens enum-typed properties to `Edm.String` — cached and cold schemas disagree (v3.0.3)

Noticed 2026-08-19 in `pragmatiqu.io` while hand-maintaining the Sales cache.

A columnar entity set may declare a backed-enum class-string instead of an `EdmPrimitiveType`;
`AbstractEntitySet::entityType()` turns that into `EnumType::fromBackedEnum($namespace, $type)`.
The **cached** schema does not: every such property comes back as

    new Property('tier', new PrimitiveType(EdmPrimitiveType::String)),

and no `EnumType` is declared in the generated `Edmx.php` at all. Checked across both host apps
(`Pragmatiqu\Sales\Edm\Types\Customer`, `Pragmatiqu\Portal\Edm\Types\MyLicense`) — consistent,
so it is the writer, not a one-off.

**Why it matters.** The warm and cold paths then serve *different* `$metadata` for the same
service. A client that binds an enum property — an `sap.ui.mdc` field with a type map, or
anything relying on `Edm.EnumType` member names — behaves differently depending on whether a
cache happens to exist. That is the same silent-divergence family as the collision item below,
and it defeats the point of `[[reference_odata_enumtype_columns]]`: the entity set deliberately
declares the enum class-string *so that* the engine emits `Edm.EnumType`.

**Shape of a fix.** `EdmxWriter` must emit the enum types into the schema's `enumTypes` and
reference them from the property, mirroring what `entityType()` builds. A regression test that
round-trips a set with one enum column through writer → loader and compares the resulting
`$metadata` to the cold path would have caught it.

**Shipped 3.0.3.** `generateTypeCode()` gained an `EnumTypeInterface` branch and the generated
`Schema` now carries `enumTypes`; three round-trip regressions guard it, including one that
asserts warm and cold coerce a backing int to the same wire value. See `CHANGELOG.md` v3.0.3.

## [x] `odata:cache` / `odata:clear` learn `--class` for route-composed services; collisions fail loud (v1.0.7)

`odata:cache` discovered services only through the `ODataServiceRegistryInterface`, so a service
served via `OData::forService()` on its own route (deliberately outside the registry — 1.0.6,
e.g. timesheet's `App\Excel\ExcelService`) could not be pre-cached and always ran the cold path.
Both commands now take `--class=FQCN1,FQCN2` — cached/cleared in addition to the registry,
validated, deduped. Plus a fail-loud pre-pass on `odata:cache` for cache-dir collisions (the
write side of the item still in Pending). See `CHANGELOG.md` v1.0.7.

## [x] `odata:cache` generates entity-set classes that collide with their type import (v1.0.5)

Surfaced in production (2026-06-04, `pragmatiqu.io`): the Portal cache fataled at
autoload on `MyDraft` / `MyOrganization` / `MyPortalState` — sets whose names equal
their entity types — because `EdmxWriter::writeEntitySet()` emitted both a
`use {ns}\Types\{Name};` import and a `class {Name}` declaration in the same file.
Pluralized sets escaped by luck, not design. Fixed by referencing the type by FQN
and dropping the import; regression-guarded by an EdmxWriter test that writes a
set-name-equals-type-name EDM and loads the generated class. See `CHANGELOG.md` v1.0.5.
