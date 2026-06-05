# LaravelUi5 OData — Roadmap

Tracks scheduled work for `laravelui5/odata`. Shipped releases live in
[`CHANGELOG.md`](./CHANGELOG.md), each tagged with the version that carried it.

- **Pending** holds items queued for an upcoming slice — design questions settled, scope known. Earlier-stage ideas live as atoms in `docs/meta/atoms/` and are lifted into Pending once they're ready to schedule.

Releases follow the consumer-bump dance: tag the package → Satis rebuilds → patch the in-house consumers (`laravelui5/core`, `laravelui5/sdk`, `pragmatiqu/timesheet.biz`) → smoke-test before the next slice begins. Companion to `laravelui5/core`'s ROADMAP; the two move together when contract surface is shared.

---

## Pending

_(nothing queued)_

---

## Done

Shipped items live in [`CHANGELOG.md`](./CHANGELOG.md) under their version. This
section keeps the roadmap-level breadcrumb — the *why it was queued* — for items
that passed through Pending.

## [x] `odata:cache` generates entity-set classes that collide with their type import (v1.0.5)

Surfaced in production (2026-06-04, `pragmatiqu.io`): the Portal cache fataled at
autoload on `MyDraft` / `MyOrganization` / `MyPortalState` — sets whose names equal
their entity types — because `EdmxWriter::writeEntitySet()` emitted both a
`use {ns}\Types\{Name};` import and a `class {Name}` declaration in the same file.
Pluralized sets escaped by luck, not design. Fixed by referencing the type by FQN
and dropping the import; regression-guarded by an EdmxWriter test that writes a
set-name-equals-type-name EDM and loads the generated class. See `CHANGELOG.md` v1.0.5.
