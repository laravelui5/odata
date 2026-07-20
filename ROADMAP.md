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
