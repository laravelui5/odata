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
